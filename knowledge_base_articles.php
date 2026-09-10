<?php include("includes/header.php"); ?>
<?php include("includes/session.php"); ?>
<?php include("includes/navbar.php") ?>
<link rel="stylesheet" href="./assets/css/kba_style.css">
<?php $page_title = "KabanDesk"; ?>

<div class="shell">
    <!-- LEFT RAIL -->
  <nav class="rail" aria-label="Categories">
    <p class="rail-heading">Categories</p>
    <ul class="cat-list" id="catList">
    

      <li class="cat-item active" data-cat="all"><span class="cat-tag">ALL</span> All articles <span class="cat-count">24</span></li>
      <li class="cat-item" data-cat="NET"><span class="cat-tag">NET</span> Network <span class="cat-count">5</span></li>
      <li class="cat-item" data-cat="POS"><span class="cat-tag">POS</span> Point of Sale <span class="cat-count">3</span></li>
      <li class="cat-item" data-cat="PMS"><span class="cat-tag">PMS</span> Property Mgmt <span class="cat-count">4</span></li>
      <li class="cat-item" data-cat="HW"><span class="cat-tag">HW</span> Hardware <span class="cat-count">3</span></li>
      <li class="cat-item" data-cat="CCTV"><span class="cat-tag">CCTV</span> Surveillance <span class="cat-count">2</span></li>
      <li class="cat-item" data-cat="MAIL"><span class="cat-tag">MAIL</span> Email <span class="cat-count">3</span></li>
      <li class="cat-item" data-cat="SW"><span class="cat-tag">SW</span> Software <span class="cat-count">4</span></li>
    </ul>

    <p class="rail-heading">Popular in Network</p>
    <ul class="article-list" id="articleList">
      <li><a href="#" class="article-link current" data-article="wifi">Wi-Fi not connecting on staff devices</a></li>
      <li><a href="#" class="article-link" data-article="vpn">Connecting to the office VPN remotely</a></li>
      <li><a href="#" class="article-link" data-article="printer">Printer offline or not printing</a></li>
      <li><a href="#" class="article-link" data-article="pos-freeze">POS terminal freezes at checkout</a></li>
      <li><a href="#" class="article-link" data-article="pms-reset">Resetting your PMS password</a></li>
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
      <button class="fb-btn" data-fb="yes">👍 Yes</button>
      <button class="fb-btn" data-fb="no">👎 No</button>
      <span class="fb-note" id="fbNote"></span>
    </div>
  </main>

  
</div>

<?php include("includes/footer.php"); ?>