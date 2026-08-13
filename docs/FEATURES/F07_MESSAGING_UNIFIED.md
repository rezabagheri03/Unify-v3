# F07 Unified Messaging Inbox - V9 Shared Host (Polling)

## Purpose
Single inbox all system messages professor announcements expert/admin targeted system notifications threading edit/delete read status, polling instead of WebSocket

## Concepts
Message can be broadcast to specification (class) OR private to recipient_id, Inbox per user list where recipient_id=self OR specification_id in user's enrolled specs broadcast, Thread reply via parent_message_id creates conversation

## Message Types
Broadcast sender professor/expert/admin specification_id set recipient_id null -> sent to all enrolled current semester, Private sender staff recipient_id single student spec nullable, System sender system null recipient_id student

## Inbox UI
Tabs All Unread Classes broadcast Private System with counts badge, Search subject/body/sender, Pull to refresh, Virtualized messages sorted sent_at desc infinite 20 per page, Row Avatar sender name bold if unread Subject bold if unread Body preview 80 chars date Shamsi Read dot blue if unread Edited badge Deleted placeholder "این پیام حذف شد" italic Priority badge high, Swipe left mark read/unread, Swipe right? Student cannot delete inbox only professor can delete own, but can mark read
Polling: Every 15s GET /api/notifications/unread + GET /api/v1/messages?tab= for new messages

## Detail Thread View
Header Subject Sender name date Shamsi priority, Body full is_edited badge edited_at tooltip is_deleted placeholder, Thread chain parent->children sorted asc chat bubbles Sender self student right blue other left gray body sent_at edited, If broadcast banner "ارسال به کل کلاس {course}", Reply section bottom textarea + Send button if broadcast reply creates private thread to original sender professor, If system reply hidden, If deleted reply hidden placeholder

## Edit/Delete by Professor
Only sender can edit/delete own message, not deleted already, Edit PATCH /api/v1/messages/{id} {subject, body} sets is_edited true edited_at now updates row triggers polling update to recipients inbox: message updated event via polling, inbox row updates real-time 15s, push already sent irreversible documented info in edit modal "پوش ارسال شده قابل بازگشت نیست", Delete DELETE sets is_deleted true deleted_at now body replaced placeholder "این پیام توسط فرستنده حذف شد" but row kept for consistency, polling shows placeholder, not hard remove, AuditLog major_edit/deletion

## Read Status
MessageReadStatus {message_id, user_id, read_at, UNIQUE}, When user opens detail marks read POST /api/v1/messages/{id}/read creates row, Unread count badge bottom nav Inbox icon count unread where no read status, polling for read sync

## Broadcast Fan-Out
When professor sends broadcast to spec with N enrolled 50, server does NOT create N rows, creates ONE row spec_id set recipient_id null, Inbox query student does messages where recipient_id=self OR (specification_id IN my enrolled spec ids AND is_deleted=0) single row serves all, read status per user via MessageReadStatus, For private group targeted messaging via Expert sending to 50 IDs create 50 rows each recipient_id single rate limit

## Offline V9
Inbox list cached Workbox runtime cache, detail cached, read status queued IndexedDB, reply queued POST send queued, edit/delete requires online

## API V9 Laravel
GET /api/v1/messages?tab=all/unread/classes/private/system&page=&search=
GET /api/v1/messages/{id} includes thread children
POST /api/v1/messages/send {recipient_id OR specification_id OR recipient_ids array group, subject, body, parent_message_id optional} Idempotency-Key MySQL table
PATCH /api/v1/messages/{id} edit
DELETE /api/v1/messages/{id} soft delete
POST /api/v1/messages/{id}/read

## Notifications V9 Polling + Pushe
On new message: Polling will fetch within 15s + if Pushe enabled server calls Pushe API via curl to recipient(s) except muted NotificationMute, push payload title subject body preview message_id type message, If recipient muted spec no push but inbox still shows
