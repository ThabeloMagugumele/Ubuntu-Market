<?php
$pageTitle = 'Messages';
require_once 'includes/functions.php';
requireLogin();

$pdo    = getDB();
$userId = (int)$_SESSION['user_id'];

// send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF($_POST['csrf_token'] ?? '')) {
    $toId      = (int)($_POST['to_id'] ?? 0);
    $body      = sanitize($_POST['body'] ?? '');
    $listingId = (int)($_POST['listing_id'] ?? 0) ?: null;
    $subject   = sanitize($_POST['subject'] ?? '');

    if ($toId && strlen($body) >= 2 && $toId !== $userId) {
        $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, listing_id, body) VALUES (?,?,?,?)')
            ->execute([$userId, $toId, $listingId, $body]);
        setFlash('success', 'Message sent!');
    }
    header('Location: messages.php?with=' . $toId);
    exit;
}

// load conversations
$convos = $pdo->prepare(
    'SELECT DISTINCT
        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_id,
        u.first_name, u.last_name, u.profile_image,
        MAX(m.sent_at) AS last_msg_time,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
     FROM messages m
     JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
     WHERE m.sender_id = ? OR m.receiver_id = ?
     GROUP BY other_id
     ORDER BY last_msg_time DESC'
);
$convos->execute([$userId, $userId, $userId, $userId, $userId]);
$conversations = $convos->fetchAll();

// get the active conversation
$withId  = (int)($_GET['with'] ?? $_GET['to'] ?? ($conversations[0]['other_id'] ?? 0));
$listing = (int)($_GET['listing'] ?? 0);

$thread = [];
if ($withId) {
    // mark messages as read
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?')->execute([$userId, $withId]);

    $stmt = $pdo->prepare(
        'SELECT m.*, u.first_name, u.last_name, u.profile_image
         FROM messages m JOIN users u ON m.sender_id = u.id
         WHERE (m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?)
         ORDER BY m.sent_at ASC LIMIT 100'
    );
    $stmt->execute([$userId, $withId, $withId, $userId]);
    $thread = $stmt->fetchAll();

    $otherUser = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $otherUser->execute([$withId]);
    $otherUser = $otherUser->fetch();

    if ($listing) {
        $listingItem = getListing($listing);
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="container py-4">
  <h2 class="section-title mb-4"><i class="bi bi-chat-dots me-2 text-warning"></i>Messages</h2>

  <div class="row g-3" style="height:600px">
    <!-- Conversations List -->
    <div class="col-md-4 col-lg-3">
      <div class="bg-white rounded-14 shadow-sm h-100 d-flex flex-column">
        <div class="p-3 border-bottom fw-bold">Conversations</div>
        <div class="flex-grow-1 overflow-auto">
          <?php if (empty($conversations)): ?>
          <div class="p-4 text-center text-muted small">No messages yet.<br>Buy an item and message a seller!</div>
          <?php else: ?>
          <?php foreach ($conversations as $c): ?>
          <a href="messages.php?with=<?= $c['other_id'] ?>"
             class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark <?= $c['other_id'] == $withId ? 'bg-warning bg-opacity-10' : 'bg-white' ?> hover-bg-light">
            <img src="<?= SITE_URL ?>/<?= sanitize($c['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
                 width="42" height="42" class="rounded-circle object-fit-cover" alt="">
            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold small"><?= sanitize($c['first_name'] . ' ' . $c['last_name']) ?></div>
              <div class="text-muted" style="font-size:.72rem"><?= timeAgo($c['last_msg_time']) ?></div>
            </div>
            <?php if ($c['unread'] > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= $c['unread'] ?></span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Chat Window -->
    <div class="col-md-8 col-lg-9">
      <div class="bg-white rounded-14 shadow-sm h-100 d-flex flex-column">
        <?php if ($withId && isset($otherUser)): ?>
        <!-- Header -->
        <div class="p-3 border-bottom d-flex align-items-center gap-2">
          <img src="<?= SITE_URL ?>/<?= sanitize($otherUser['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
               width="40" height="40" class="rounded-circle object-fit-cover">
          <div>
            <div class="fw-bold"><?= sanitize($otherUser['first_name'] . ' ' . $otherUser['last_name']) ?></div>
            <?php if (!empty($listingItem)): ?>
            <div class="small text-muted">Re: <a href="listing.php?id=<?= $listingItem['id'] ?>"><?= sanitize($listingItem['title']) ?></a></div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Messages -->
        <div class="messages-box flex-grow-1 overflow-auto p-3" id="messagesBox">
          <?php if (empty($thread)): ?>
          <div class="text-center text-muted py-4 small">No messages yet. Start the conversation!</div>
          <?php else: ?>
          <?php foreach ($thread as $msg): ?>
          <div class="d-flex <?= $msg['sender_id'] == $userId ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
            <?php if ($msg['sender_id'] != $userId): ?>
            <img src="<?= SITE_URL ?>/<?= sanitize($msg['profile_image'] ?? 'assets/images/default_avatar.png') ?>"
                 width="30" height="30" class="rounded-circle object-fit-cover me-2 mt-1" style="flex-shrink:0">
            <?php endif; ?>
            <div>
              <div class="<?= $msg['sender_id'] == $userId ? 'message-bubble-sent' : 'message-bubble-recv' ?>">
                <?= nl2br(sanitize($msg['body'])) ?>
              </div>
              <div class="text-muted mt-1" style="font-size:.7rem;text-align:<?= $msg['sender_id'] == $userId ? 'right' : 'left' ?>"><?= timeAgo($msg['sent_at']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Input -->
        <form method="POST" class="p-3 border-top d-flex gap-2">
          <?= csrfField() ?>
          <input type="hidden" name="to_id" value="<?= $withId ?>">
          <input type="hidden" name="listing_id" value="<?= $listing ?>">
          <input type="text" name="body" class="form-control" placeholder="Type a message..." required autocomplete="off">
          <button type="submit" class="btn btn-warning px-3"><i class="bi bi-send-fill"></i></button>
        </form>

        <?php else: ?>
        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
          <div class="text-center"><i class="bi bi-chat-dots" style="font-size:3rem;opacity:0.2"></i><br>Select a conversation</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-scroll to bottom
const box = document.getElementById('messagesBox');
if (box) box.scrollTop = box.scrollHeight;
</script>

<?php include 'includes/footer.php'; ?>
