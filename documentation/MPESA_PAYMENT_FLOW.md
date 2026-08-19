# M-Pesa Payment Verification Flow

## How the System Knows if Payment was Successful or Failed

The system uses **TWO mechanisms** to detect payment status:

---

## Method 1: Safaricom Callback (Instant) ⚡

When a user completes or cancels a payment on their phone, Safaricom immediately sends a notification to your server.

### Flow:
```
1. User clicks "Send M-Pesa Prompt" → STK Push sent to phone
2. User enters PIN on phone → Completes payment
3. Safaricom instantly POSTs to: backend/mpesa/callback.php
4. Callback processes the result and updates database
```

### What the Callback Does:

#### ✅ **If Payment Successful (ResultCode = 0):**
1. Updates payment status to `completed`
2. Saves M-Pesa receipt number (e.g., `QGH7KLMN23`)
3. **Records goal contribution** (adds amount to savings goal)
4. **Creates expense record** automatically with:
   - Category: "Savings" or "Bills & Utilities"
   - Description: "M-Pesa QGH7KLMN23 (Goal funding)"
   - Amount: Payment amount
   - Date: Today
5. Updates goal's `current_amount` (recalculates from all contributions)

#### ❌ **If Payment Failed/Cancelled:**
- **ResultCode 1032:** User cancelled on phone → Status = `cancelled`
- **Other error codes:** Payment failed → Status = `failed`
- Saves error description (e.g., "Request cancelled by user", "Insufficient funds")
- **NO expense or goal contribution is created**

### Callback Location:
- File: `backend/mpesa/callback.php`
- Must be publicly accessible (use ngrok for localhost testing)
- URL set in: `backend/config/mpesa.php` → `CALLBACK_URL`

---

## Method 2: Polling/Query (Backup) 🔄

In case the callback doesn't fire (network issues, ngrok down, etc.), the frontend actively checks payment status every 5 seconds.

### Flow:
```
1. Frontend sends STK Push
2. Every 5 seconds: Frontend calls backend/mpesa/payment_status.php
3. Backend checks database status
4. If still "pending" after 5+ seconds: Backend queries Safaricom's STK Query API
5. Updates database and returns status to frontend
```

### What Payment Status Does:

#### After 5 seconds of pending status:
1. Calls Safaricom's **STK Push Query API** to check real-time status
2. Safaricom responds with current payment state

#### ✅ **If Query Returns Success (ResultCode = 0):**
1. Updates payment status to `completed`
2. Saves M-Pesa receipt number
3. **Records goal contribution** (same as callback)
4. **Creates expense record** (same as callback)
5. Updates goal's `current_amount`
6. **Prevents duplicates** (checks if contribution/expense already exists)

#### ❌ **If Query Returns Failed/Cancelled:**
- Updates status to `failed` or `cancelled`
- Saves error description
- **NO expense or goal contribution is created**

### Polling Location:
- File: `backend/mpesa/payment_status.php`
- Frontend polls this every 5 seconds for up to 60 seconds
- JavaScript: `frontend/mpesa.html` → `startPolling()` function

---

## Complete Payment Lifecycle Example

### Scenario 1: Successful Payment ✅

```
Time   | Event                                  | Status    | Database Updates
-------|----------------------------------------|-----------|------------------
00:00  | User clicks "Send M-Pesa Prompt"      | pending   | mpesa_payments: 1 record
00:02  | Phone receives STK push               | pending   | -
00:05  | User enters PIN                       | pending   | -
00:07  | Safaricom processes payment           | pending   | -
00:08  | Callback fires OR Query detects       | completed | - Payment marked completed
       |                                        |           | - Receipt saved
       |                                        |           | - Goal contribution added
       |                                        |           | - Expense created
       |                                        |           | - Goal amount updated
00:08  | Frontend detects completed            | completed | - Shows success message
       |                                        |           | - Displays receipt number
```

**Result:**
- ✅ Payment status: `completed`
- ✅ Goal contribution recorded
- ✅ Expense created and visible in Expenses page
- ✅ Goal progress bar updated
- ✅ Dashboard shows new expense

---

### Scenario 2: User Cancels Payment ❌

```
Time   | Event                                  | Status    | Database Updates
-------|----------------------------------------|-----------|------------------
00:00  | User clicks "Send M-Pesa Prompt"      | pending   | mpesa_payments: 1 record
00:02  | Phone receives STK push               | pending   | -
00:05  | User clicks "Cancel" on phone         | pending   | -
00:07  | Callback fires OR Query detects       | cancelled | - Payment marked cancelled
       |                                        |           | - Description: "Request cancelled by user"
       |                                        |           | - NO goal contribution
       |                                        |           | - NO expense created
00:07  | Frontend detects cancelled            | cancelled | - Shows failure message
       |                                        |           | - Displays reason
```

**Result:**
- ❌ Payment status: `cancelled`
- ❌ NO goal contribution
- ❌ NO expense created
- ❌ Goal progress unchanged
- User can try again

---

### Scenario 3: Wrong PIN / Insufficient Funds ❌

```
Time   | Event                                  | Status    | Database Updates
-------|----------------------------------------|-----------|------------------
00:00  | User clicks "Send M-Pesa Prompt"      | pending   | mpesa_payments: 1 record
00:02  | Phone receives STK push               | pending   | -
00:05  | User enters wrong PIN (3 times)       | pending   | -
00:07  | Safaricom rejects payment             | pending   | -
00:08  | Callback fires OR Query detects       | failed    | - Payment marked failed
       |                                        |           | - Description: "Wrong PIN" or "Insufficient funds"
       |                                        |           | - NO goal contribution
       |                                        |           | - NO expense created
00:08  | Frontend detects failed               | failed    | - Shows failure message
       |                                        |           | - Displays error reason
```

**Result:**
- ❌ Payment status: `failed`
- ❌ NO goal contribution
- ❌ NO expense created
- ❌ Goal progress unchanged
- User can try again

---

## Common M-Pesa Result Codes

| Code | Meaning | System Action |
|------|---------|---------------|
| `0` | Success | ✅ Record payment, goal contribution, expense |
| `1` | Insufficient funds | ❌ Mark failed, no records |
| `1032` | User cancelled | ❌ Mark cancelled, no records |
| `1037` | Wrong PIN | ❌ Mark failed, no records |
| `1` | Timeout (user didn't respond) | ❌ Mark failed, no records |
| `2001` | Invalid initiator | ❌ Mark failed (config error) |

---

## Duplicate Prevention 🛡️

Both callback and polling now check for existing records before creating duplicates:

### Goal Contributions:
```sql
SELECT COUNT(*) FROM goal_contributions 
WHERE goal_id = ? AND description LIKE '%RECEIPT%' AND amount = ?
```
- If exists: Skip insertion
- If not exists: Create contribution

### Expenses:
```sql
SELECT COUNT(*) FROM expenses 
WHERE user_id = ? AND description LIKE '%M-Pesa RECEIPT%' AND amount = ?
```
- If exists: Skip insertion
- If not exists: Create expense

This ensures that even if both callback AND polling fire, you won't get duplicate records.

---

## Testing Payment Verification

### Test Successful Payment:
1. Go to M-Pesa page
2. Select a goal
3. Enter sandbox test number: `254708374149`
4. Enter amount: `10`
5. Click "Send M-Pesa Prompt"
6. Enter sandbox PIN: `1234`
7. **Watch the frontend:** Status changes to "Completed" within 5-10 seconds
8. **Check database:** `mpesa_payments` status = `completed`
9. **Check Goals page:** Goal amount increased by 10
10. **Check Expenses page:** New expense "M-Pesa [receipt] (Goal funding)" appears

### Test Failed Payment:
1. Same steps as above
2. Enter wrong PIN: `0000`
3. **Watch the frontend:** Status changes to "Failed" with reason
4. **Check database:** `mpesa_payments` status = `failed`
5. **Check Goals page:** Goal amount unchanged
6. **Check Expenses page:** NO new expense

### Test Cancelled Payment:
1. Same steps as above
2. Click "Cancel" on phone STK prompt
3. **Watch the frontend:** Status changes to "Cancelled"
4. **Check database:** `mpesa_payments` status = `cancelled`
5. **Check Goals page:** Goal amount unchanged
6. **Check Expenses page:** NO new expense

---

## Troubleshooting

### Payment stuck on "pending"?
1. **Check internet connection** (both yours and Safaricom's sandbox)
2. **Check callback URL** in `backend/config/mpesa.php` - must be publicly accessible
3. **Check server logs**: `error_log` will show callback/query errors
4. **Manual query**: Wait 60 seconds, payment will auto-expire or backend will query status

### Payment successful but no goal contribution?
1. **Check payment record**: `SELECT * FROM mpesa_payments WHERE payment_id = X`
2. **Verify goal_id**: Must match existing goal owned by user
3. **Check contributions table**: `SELECT * FROM goal_contributions WHERE goal_id = X`
4. **Check for errors**: Look in server error logs

### Callback not firing?
1. **Ngrok running?** Must be active for localhost callback
2. **Correct URL?** Update `CALLBACK_URL` in `mpesa.php` with current ngrok URL
3. **Fallback works:** Polling will detect status within 5-10 seconds even without callback

---

## Architecture Summary

```
┌─────────────────┐
│   User Phone    │ ← STK Push sent here
└────────┬────────┘
         │ User enters PIN
         ↓
┌─────────────────┐
│   Safaricom     │
│   Daraja API    │
└────┬───────┬────┘
     │       │
     │       └──────────────┐
     │                      │
     │ Callback             │ Query API
     │ (instant)            │ (polling)
     ↓                      ↓
┌─────────────────┐   ┌──────────────────┐
│  callback.php   │   │payment_status.php│
└────────┬────────┘   └────────┬─────────┘
         │                     │
         └──────────┬──────────┘
                    ↓
         ┌──────────────────┐
         │     Database     │
         │  mpesa_payments  │
         │goal_contributions│
         │     expenses     │
         └──────────────────┘
                    ↑
                    │
         ┌──────────────────┐
         │    Frontend      │
         │   (mpesa.html)   │
         │  Polls every 5s  │
         └──────────────────┘
```

---

## Key Files

| File | Purpose |
|------|---------|
| `backend/mpesa/stk_push.php` | Initiates payment request |
| `backend/mpesa/callback.php` | Receives instant payment result from Safaricom |
| `backend/mpesa/payment_status.php` | Polls payment status + queries Daraja if needed |
| `backend/mpesa/MpesaService.php` | M-Pesa API wrapper (OAuth, STK Push, Query) |
| `backend/config/mpesa.php` | M-Pesa configuration and credentials |
| `frontend/mpesa.html` | User interface + polling logic |

---

## Summary

✅ **System tracks payment status through TWO mechanisms:**
1. **Callback** (instant notification from Safaricom)
2. **Polling + Query** (frontend checks every 5s, backend queries Safaricom after 5s)

✅ **On SUCCESS:**
- Payment marked `completed`
- Receipt saved
- Goal contribution recorded
- Expense created automatically
- Goal amount updated

❌ **On FAILURE/CANCEL:**
- Payment marked `failed` or `cancelled`
- Error reason saved
- **NO goal contribution**
- **NO expense created**

🛡️ **Duplicate prevention** ensures no double-recording even if both callback and polling fire.

🔄 **Works without callback** - polling is fully functional backup!
