# Chat System Testing Guide

## Prerequisites

Before testing, ensure these services are running:

### 1. Backend (Laravel)
```bash
cd e:\NEW PROJECTS\ISELCO1STAR V2\iselco-backend
php artisan serve
```
Should run on: `http://localhost:8000`

### 2. Laravel Reverb (WebSocket Server)
```bash
cd e:\NEW PROJECTS\ISELCO1STAR V2\iselco-backend
php artisan reverb:start --debug
```
Should run on: `ws://localhost:8080`

### 3. Frontend (Ionic Vue)
```bash
cd e:\NEW PROJECTS\ISELCO1STAR V2\iselco-frontend
npm run dev
```
Should run on: `http://localhost:5173`

---

## Testing Steps

### Test 1: Two-User Real-Time Chat

1. **Open two browser windows** (or use Incognito + Regular):
   - Window 1: Login as **tech** user
   - Window 2: Login as **accountant** user

2. **Both users navigate to** `tkt-001` ticket detail page

3. **Check WebSocket connection** in browser console (F12):
   ```
   ✅ Subscribed to ticket.XXX
   ```

4. **Window 1 (tech)**: Send a message "Hello from tech"
   - Message should appear on **right side** (teal bubble) in Window 1
   - Message should appear on **left side** (gray bubble) in Window 2 **INSTANTLY**

5. **Window 2 (accountant)**: Reply with "Hi from accountant"
   - Message should appear on **right side** (teal bubble) in Window 2
   - Message should appear on **left side** (gray bubble) in Window 1 **INSTANTLY**

6. **Expected behavior**:
   - ✅ Messages appear without page refresh
   - ✅ Your messages on right (teal gradient)
   - ✅ Other users' messages on left (gray)
   - ✅ Auto-scroll to bottom on new messages
   - ✅ Smooth animations

---

### Test 2: UI Verification

Check the following in the chat interface:

- ✅ **Left bubbles (others)**: Gray background, user avatar visible, sender name shown
- ✅ **Right bubbles (you)**: Teal gradient, no avatar, no sender name
- ✅ **Timestamps**: Relative time ("Just now", "5m ago", etc.)
- ✅ **Edit/Delete buttons**: Appear on hover for your own messages
- ✅ **Auto-scroll**: Messages scroll to bottom automatically
- ✅ **Scroll button**: Appears when scrolled up, clicking scrolls to bottom

---

### Test 3: Edit and Delete

1. **Send a message**
2. **Hover over your message** → Edit button appears
3. **Click Edit** → Input field shows message
4. **Modify and save** → Message updates in real-time for both users
5. **Hover and Delete** → Message removed instantly for both users

---

### Test 4: Multi-User (5+ Users)

1. Open **5 browser tabs** (mix of incognito/regular/different browsers)
2. Login with different users in each tab
3. All navigate to same ticket
4. Send messages from different tabs
5. **Verify**: All tabs receive all messages instantly

---

### Test 5: Network Reconnection

1. **Disconnect internet** (turn off WiFi)
2. **Wait 5 seconds**
3. **Reconnect internet**
4. Try sending a message
5. **Expected**: WebSocket reconnects automatically, message sends successfully

---

## Troubleshooting

### Issue: Messages not appearing in real-time

**Check**:
1. Laravel Reverb is running (`php artisan reverb:start`)
2. Browser console shows: `✅ Subscribed to ticket.XXX`
3. Backend `.env` has `BROADCAST_CONNECTION=reverb`
4. Frontend `.env` has correct Reverb settings

**Solution**:
```bash
# Restart Reverb with debug mode
php artisan reverb:restart --debug
```

### Issue: "401 Unauthorized" in WebSocket

**Check**:
1. User is logged in
2. Token is valid
3. Broadcasting routes are registered

**Solution**:
```bash
# Check routes
php artisan route:list | grep broadcast
```

### Issue: Bubbles not showing left/right correctly

**Check**:
1. CommentItem component is rendering
2. Console shows no errors
3. User ID comparison is working

**Solution**: Clear browser cache and hard refresh (Ctrl+Shift+R)

---

## Performance Testing (400+ Users)

For load testing, you'll need tools like **Artillery** or **k6**:

```bash
# Install Artillery (optional)
npm install -g artillery

# Create load test (example)
artillery quick --count 100 --num 10 http://localhost:8000/api/tickets
```

But for basic verification, **5-10 concurrent browser tabs** is sufficient.

---

## Expected Console Logs

When everything is working, you'll see:

```
✅ Subscribed to ticket.1
📩 New comment received: 123
✏️ Comment updated: 123
🗑️ Comment deleted: 123
```

---

## Success Criteria

✅ Messages appear instantly without refresh  
✅ Sender messages on right (teal), others on left (gray)  
✅ Avatars only for other users  
✅ Auto-scroll works smoothly  
✅  Edit and delete sync in real-time  
✅ Multiple users can chat simultaneously  
✅ No delays or lag  
✅ Clean, messenger-like UI

---

**Ready to test?** Follow the steps above and verify everything works!
