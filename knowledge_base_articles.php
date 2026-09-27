<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<link rel="stylesheet" href="./assets/css/kba_style.css">
<?php $page_title = "KabanDesk"; ?>

<div class="shell">
    <!-- LEFT RAIL -->
  <?php
// Total article count (for "All articles")
$totalCount = retrieve("SELECT COUNT(*) AS total FROM kb_articles", array());
$totalArticles = $totalCount[0]['total'] ?? 0;

// Categories with their article counts in one query (avoids N+1 queries)
$getCat = retrieve(
    "SELECT c.id, c.code, c.name, COUNT(a.id) AS article_count
     FROM categories c
     LEFT JOIN kb_articles a ON a.category_id = c.id
     GROUP BY c.id, c.code, c.name
     ORDER BY c.name ASC",
    array()
);

// Popular articles in the Network category (top 5 by views)
$popularNetwork = retrieve(
    "SELECT a.id, a.title, a.slug
     FROM kb_articles a
     INNER JOIN categories c ON a.category_id = c.id
     WHERE c.code = ?
     ORDER BY a.views DESC
     LIMIT 5",
    array('NET')
);
?>

<nav class="rail" aria-label="Categories">
    <p class="rail-heading">Categories</p>
    <ul class="cat-list" id="catList">

      <li class="cat-item active" data-cat="all">
        <span class="cat-tag">ALL</span> All articles
        <span class="cat-count"><?= htmlspecialchars($totalArticles) ?></span>
      </li>

      <?php foreach ($getCat as $cat): ?>
        <li class="cat-item" data-cat="<?= htmlspecialchars($cat['code']) ?>">
          <span class="cat-tag"><?= htmlspecialchars($cat['code']) ?></span>
          <?= htmlspecialchars($cat['name']) ?>
          <span class="cat-count"><?= htmlspecialchars($cat['article_count']) ?></span>
        </li>
      <?php endforeach; ?>

    </ul>

    <p class="rail-heading">Popular in Network</p>
    <ul class="article-list" id="articleList">
      <?php if (empty($popularNetwork)): ?>
        <li class="article-empty">No articles yet</li>
      <?php else: ?>
        <?php foreach ($popularNetwork as $i => $article): ?>
          <li>
            <a href="#"
               class="article-link<?= $i === 0 ? ' current' : '' ?>"
               data-article="<?= htmlspecialchars($article['slug']) ?>">
              <?= htmlspecialchars($article['title']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
</nav>

  <!-- ARTICLE -->
  <main class="article-col">
    <div class="breadcrumb">
      <a href="#">Knowledge Base</a><span class="sep">/</span>
      <a href="#">Network</a><span class="sep">/</span>
      <span class="current-crumb" id="crumbCurrent">Wi-Fi not connecting</span>
    </div>

    <div class="article-meta-top">
      <span class="status-pill">NET</span>
      <span class="article-id">KB-0142</span>
    </div>

    <h1 class="article-title" id="articleTitle">Wi-Fi not connecting on staff devices</h1>

    <div class="article-subline">
      <span>By <strong>IT Support Specialist</strong></span>
      <span class="dot"></span>
      <span>Updated <strong>Aug 22, 2026</strong></span>
      <span class="dot"></span>
      <span><strong>412</strong> views</span>
      <span class="dot"></span>
      <span><strong>91%</strong> found this helpful</span>
    </div>

    <div class="article-body" id="articleBody">
      <p>Staff Wi-Fi drops most often after a router reboot or when a device has cached an old network password. Work through the steps below in order — most cases are resolved by step 2.</p>

      <h2 id="sec-check">1. Confirm the network name</h2>
      <p>Staff devices should connect to <code class="inline">Kaban-Staff-5G</code>, not the guest network. If your device shows <code class="inline">Kaban-Staff</code> without <code class="inline">-5G</code>, you're on the older 2.4GHz band, which is slower but should still connect.</p>

      <ol>
        <li>Open Wi-Fi settings and forget any network starting with <code class="inline">Kaban-Staff</code>.</li>
        <li>Turn Wi-Fi off, wait 10 seconds, then turn it back on.</li>
        <li>Reconnect to <code class="inline">Kaban-Staff-5G</code> using the current password posted in the IT channel.</li>
        <li>If prompted for a certificate, choose <strong>Trust</strong> — this is expected on hotel-managed devices.</li>
      </ol>

      <div class="callout">
        <strong>If the network doesn't appear at all:</strong> you're likely out of range of an access point. Check the floor plan in the IT office for the nearest AP, or move closer to a hallway unit.
      </div>

      <h2 id="sec-still">2. Still not connecting?</h2>
      <p>A small number of devices hold onto an expired IP lease even after reconnecting. Restarting clears this in most cases.</p>
      <ul>
        <li>Restart the device fully (not sleep — a full power cycle).</li>
        <li>On Android, clear cache for the Wi-Fi/Connectivity system app.</li>
        <li>On Windows laptops, run <code class="inline">ipconfig /release</code> then <code class="inline">ipconfig /renew</code> from an elevated prompt.</li>
      </ul>

      <h2 id="sec-escalate">3. When to escalate</h2>
      <p>If more than one device on the same floor can't connect, this is likely an access point issue rather than a device issue — escalate immediately rather than repeating these steps on every affected device.</p>
    </div>

    <div class="escalation-box">
      <div class="esc-text">
        <strong>Still stuck?</strong>
        Submit a ticket and we'll pick it up under category NET.
      </div>
      <button class="esc-btn">Submit a ticket</button>
    </div>

    <div class="feedback-row">
      <span>Was this article helpful?</span>
      <button class="fb-btn" data-fb="yes"><span class="fa fa-thumbs-up"></span>  Yes</button>
      <button class="fb-btn" data-fb="no"><span class="fa fa-thumbs-down"></span> No</button>
      <span class="fb-note" id="fbNote"></span>
    </div>
  </main>

  
</div>

<?php include("includes/footer.php"); ?>