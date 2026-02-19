<?php include 'header.php'; ?>
<main class="container">
  <h2>Desktop Layout (Original 12-column system)</h2>
  <div class="row">
    <div class="col col-12">col-12</div>
  </div>
  <div class="row">
    <div class="col col-6">col-6</div>
    <div class="col col-6">col-6</div>
  </div>
  <div class="row">
    <div class="col col-4">col-4</div>
    <div class="col col-4">col-4</div>
    <div class="col col-4">col-4</div>
  </div>

  <h2>Tablet Responsive (769px-1024px)</h2>
  <div class="row">
    <div class="col col-12 col-tablet-2">Desktop: Full | Tablet: 50%</div>
    <div class="col col-12 col-tablet-2">Desktop: Full | Tablet: 50%</div>
  </div>
  <div class="row">
    <div class="col col-6 col-tablet-3">Desktop: 50% | Tablet: 33%</div>
    <div class="col col-6 col-tablet-3">Desktop: 50% | Tablet: 33%</div>
    <div class="col col-12 col-tablet-3">Desktop: Full | Tablet: 33%</div>
  </div>
  <div class="row">
    <div class="col col-3 col-tablet-4">Desktop: 25% | Tablet: 25%</div>
    <div class="col col-3 col-tablet-4">Desktop: 25% | Tablet: 25%</div>
    <div class="col col-3 col-tablet-4">Desktop: 25% | Tablet: 25%</div>
    <div class="col col-3 col-tablet-4">Desktop: 25% | Tablet: 25%</div>
  </div>

  <h2>Mobile Responsive (768px and below)</h2>
  <div class="row">
    <div class="col col-6 col-mobile-2">Desktop: 50% | Mobile: 50%</div>
    <div class="col col-6 col-mobile-2">Desktop: 50% | Mobile: 50%</div>
  </div>
  <div class="row">
    <div class="col col-4 col-mobile-3">Desktop: 33% | Mobile: 33%</div>
    <div class="col col-4 col-mobile-3">Desktop: 33% | Mobile: 33%</div>
    <div class="col col-4 col-mobile-3">Desktop: 33% | Mobile: 33%</div>
  </div>
  <div class="row">
    <div class="col col-3">Desktop: 25% | Mobile: Full (no mobile class)</div>
    <div class="col col-3 col-mobile-2">Desktop: 25% | Mobile: 50%</div>
    <div class="col col-3 col-mobile-2">Desktop: 25% | Mobile: 50%</div>
    <div class="col col-3">Desktop: 25% | Mobile: Full (no mobile class)</div>
  </div>

  <h2>Combined Responsive (Tablet + Mobile)</h2>
  <div class="row">
    <div class="col col-12 col-tablet-2 col-mobile-2">Desktop: Full | Tablet: 50% | Mobile: 50%</div>
    <div class="col col-12 col-tablet-2 col-mobile-2">Desktop: Full | Tablet: 50% | Mobile: 50%</div>
  </div>
  <div class="row">
    <div class="col col-6 col-tablet-3 col-mobile-3">Desktop: 50% | Tablet: 33% | Mobile: 33%</div>
    <div class="col col-6 col-tablet-3 col-mobile-3">Desktop: 50% | Tablet: 33% | Mobile: 33%</div>
    <div class="col col-12 col-tablet-3">Desktop: Full | Tablet: 33% | Mobile: Full</div>
  </div>
</main>

<section class="fullwidth">
  <h2>Full-Width Section Examples</h2>
  <div class="row">
    <div class="col col-4 col-tablet-2 col-mobile-2">Desktop: 33% | Tablet: 50% | Mobile: 50%</div>
    <div class="col col-4 col-tablet-2 col-mobile-2">Desktop: 33% | Tablet: 50% | Mobile: 50%</div>
    <div class="col col-4">Desktop: 33% | Mobile: Full</div>
  </div>
  
  <div class="row">
    <div class="col col-3">
      <div class="sticky-scroll">
        <h3>Sticky Element</h3>
        <p>This stays sticky on desktop/tablet, static on mobile.</p>
      </div>
    </div>
    <div class="col col-9">Main content area</div>
  </div>
</section>

<section class="sticky-container">
  <div class="sticky-wrapper">
    <div class="sticky-scroll">
      <h3>Sticky Scroll Element</h3>
      <p>This element scrolls with the page until the parent ends.</p>
    </div>
    <div class="scroll-content">
      <p>This is scrollable content that creates height.</p>
      <div style="height: 2000px;"></div>
    </div>
  </div>
</section>
<?php include 'footer.php'; ?>