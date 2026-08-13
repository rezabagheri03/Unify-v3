# P07 Student Inbox Messaging - V9 Shared Host (Polling + Pushe PHP)

## Routes
/inbox list, /inbox/{id} detail thread

## List Page /inbox
UI AppBar Title "صندوق پیام" + search icon + filter icon, Tabs All Unread Classes broadcast Private System with counts badge, List Virtualized messages sorted sent_at desc infinite 20 per page, Message Row Avatar professor photo placeholder or system icon Sender name bold if unread Subject bold if unread Body preview 80 chars gray Shamsi date small Read dot blue if unread Edited badge small Deleted placeholder "این پیام حذف شد" italic gray Priority badge high red Attachment icon, Swipe left mark read/unread, Swipe right? Student cannot delete inbox only professor can delete own messages but can mark read, Pull to refresh, Empty No messages illustration, Polling every 15s GET /api/notifications/unread + GET /api/v1/messages?tab= for new

Data GET /api/v1/messages?tab=all/unread/classes/private/system&page=&search= Workbox runtime cache 5min, Polling events new_message message_updated message_deleted message_read via polling endpoint, States Loading skeleton rows Offline Cached messages viewable banner offline

## Detail Page /inbox/{id} Thread View
Header Subject Sender name date Shamsi priority back button, Body Full body text selectable is_edited badge edited_at tooltip is_deleted placeholder, Thread chain parent->children sorted asc chat bubbles Sender self student right blue other left gray body sent_at edited, If broadcast banner "ارسال به کل کلاس {course}", Reply section bottom textarea + Send button if broadcast reply creates private thread to original sender professor, If system reply hidden, If deleted reply hidden placeholder, Data GET /api/v1/messages/{id} includes thread children array sorted asc, POST /api/v1/messages/{id}/read on open marks read creates MessageReadStatus, Polling thread updates real-time 15s
Reply Flow Textarea required max2000 Button Send -> POST /api/v1/messages/send {recipient_id OR specification_id for broadcast? Actually reply private recipient_id original sender subject Re: original body parent_message_id=id} idempotency key MySQL IdempotencyKeys, On success new bubble appears list inbox updates polling + Pushe PHP curl to recipient, Edit/Delete Professor only student sees result via polling message_updated/deleted, Offline Detail cached read status queued IndexedDB reply queued

## Edge V9 Shared Host
Message deleted while viewing detail polling 15s later updates to placeholder reply disabled, Thread many replies 50+ scrollable, Unread count badge bottom nav updates via polling

## API Laravel V9
GET /api/v1/messages?tab=&page=&search=
GET /api/v1/messages/{id}
POST /api/v1/messages/send
POST /api/v1/messages/{id}/read
PATCH/DELETE only professor but student page handles display via polling
