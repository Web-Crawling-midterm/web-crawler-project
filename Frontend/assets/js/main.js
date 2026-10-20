/**
 * Web Data Crawler - Frontend Logic (Sinh viên Năm 3)
 * Phục vụ đồ án môn học Web Crawling & Scraping
 * Giao diện chuẩn mực, sạch sẽ, không màu mè, tối ưu trải nghiệm học thuật
 */
(function () {
  "use strict";

  // ===== Cấu hình & Định tuyến API thông minh =====
  function getApiPath(endpoint) {
    const path = window.location.pathname;
    if (path.includes("/Frontend/") || path.includes("/frontend/") || path.endsWith("/Frontend") || path.endsWith("/frontend")) {
      return "../backend/" + endpoint;
    }
    return "backend/" + endpoint;
  }

  const API_CRAWL = getApiPath("crawl.php");
  const API_PARSE = getApiPath("parse_html.php");
  const API_SAVE = getApiPath("save_data.php");
  const API_RESULTS = getApiPath("results.php");
  const API_ROBOTS = getApiPath("check_robot.php");

  // ===== Tham chiếu DOM =====
  const $ = (id) => document.getElementById(id);

  // Crawler Form
  const form = $("crawl-form");
  const urlInput = $("url-input");
  const maxPagesInput = $("max-pages");
  const crawlBtn = $("crawl-btn");
  const btnSpinner = $("btn-spinner");
  const btnIcon = $("btn-icon");
  const btnText = $("btn-text");
  const quickCheckRobotsLink = $("quick-check-robots-link");

  // Status & Summary
  const statusArea = $("status-area");
  const summary = $("summary");
  const statItems = $("stat-items");
  const statLinks = $("stat-links");
  const statImages = $("stat-images");
  const statTime = $("stat-time");

  // Results & View Switcher
  const resultsCard = $("results-card");
  const countBadge = $("count-badge");
  const warningsBox = $("warnings");

  const pillProdCount = $("pill-prod-count");
  const pillLinkCount = $("pill-link-count");
  const pillImgCount = $("pill-img-count");

  const viewProductsContent = $("view-products-content");
  const viewLinksContent = $("view-links-content");
  const viewImagesContent = $("view-images-content");
  const viewRawContent = $("view-raw-content");
  const filterControlsArea = $("filter-controls-area");

  // Tables & Bodies
  const resultsBody = $("results-body");
  const linksTableBody = $("links-table-body");
  const imagesGridBody = $("images-grid-body");
  const rawHtmlPreview = $("raw-html-preview");
  const emptyFilter = $("empty-filter");

  // Filters
  const filterInput = $("filter-input");
  const filterRating = $("filter-rating");
  const filterStock = $("filter-stock");
  const btnResetFilter = $("btn-reset-filter");

  // Robots Tab
  const robotsForm = $("robots-form");
  const robotsTestUrl = $("robots-test-url");
  const robotsUserAgent = $("robots-user-agent");
  const btnCheckRobots = $("btn-check-robots");
  const robotsSpinner = $("robots-spinner");
  const robotsIcon = $("robots-icon");
  const robotsResultBox = $("robots-result-box");
  const robotsStatusTitle = $("robots-status-title");
  const robotsStatusBadge = $("robots-status-badge");
  const robotsStatusDesc = $("robots-status-desc");
  const robotsStatusMeta = $("robots-status-meta");

  // Saved Data Tab
  const savedSessionsCount = $("saved-sessions-count");
  const savedTotalItems = $("saved-total-items");
  const savedHistoryBody = $("saved-history-body");
  const btnRefreshSaved = $("btn-refresh-saved");
  const btnViewSavedItems = $("btn-view-saved-items");

  // ===== Trạng thái ứng dụng (Application State) =====
  const state = {
    currentView: "products", // 'products' | 'links' | 'images' | 'raw'
    items: [],               // Danh sách sản phẩm trích xuất
    links: [],               // Danh sách liên kết
    images: [],              // Danh sách hình ảnh
    rawHtml: "",             // HTML thô
    filterText: "",
    filterRating: "",
    filterStock: "",
    sortKey: null,
    sortDir: 1,              // 1: Tăng dần, -1: Giảm dần
    busy: false,
    meta: {},
    savedRecords: []
  };

  // ===== Tiện ích (Helper Functions) =====
  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function safeHttpUrl(value) {
    try {
      const u = new URL(String(value));
      return u.protocol === "http:" || u.protocol === "https:" ? u.href : null;
    } catch (e) {
      return null;
    }
  }

  function isValidUrl(value) {
    return safeHttpUrl(value.trim()) !== null;
  }

  function resolveUrl(value, base) {
    try {
      return new URL(String(value || ""), base).href;
    } catch (e) {
      return "";
    }
  }

  function formatPrice(item) {
    if (typeof item.price !== "number" || !isFinite(item.price)) return "—";
    const cur = item.currency || "GBP";
    try {
      return new Intl.NumberFormat("en-GB", { style: "currency", currency: cur }).format(item.price);
    } catch (e) {
      return item.price.toFixed(2) + " " + cur;
    }
  }

  function starsHtml(rating) {
    if (typeof rating !== "number" || rating < 0 || rating > 5) return '<span class="text-muted">—</span>';
    const full = Math.round(rating);
    let out = '<span class="rating-stars" title="' + full + ' / 5 sao">';
    for (let i = 1; i <= 5; i++) {
      out += i <= full
        ? '<i class="bi bi-star-fill"></i>'
        : '<i class="bi bi-star-fill star-muted"></i>';
    }
    return out + "</span>";
  }

  function stockBadge(text) {
    if (!text) return '<span class="text-muted">—</span>';
    const ok = /in stock/i.test(text);
    return '<span class="badge ' + (ok ? "badge-stock-in" : "badge-stock-out") + '">' + escapeHtml(text) + "</span>";
  }

  // ===== Trạng thái giao diện =====
  function setStatus(type, message, code) {
    // type: info | success | danger | warning
    const icons = {
      info: "bi-hourglass-split",
      success: "bi-check-circle-fill",
      danger: "bi-x-octagon-fill",
      warning: "bi-exclamation-triangle-fill"
    };
    const codeHtml = code ? '<div class="small opacity-75 mt-0.5">Mã phản hồi: ' + escapeHtml(code) + "</div>" : "";
    statusArea.innerHTML =
      '<div class="alert alert-' + type + ' d-flex align-items-center gap-2 mb-3 py-2.5" role="alert">' +
      '<i class="bi ' + (icons[type] || icons.info) + ' fs-5"></i>' +
      '<div class="flex-grow-1"><div class="fw-semibold">' + escapeHtml(message) + "</div>" + codeHtml + "</div>" +
      '<button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Đóng"></button>' +
      '</div>';
  }

  function clearStatus() {
    statusArea.innerHTML = "";
  }

  function setBusy(busy) {
    state.busy = busy;
    crawlBtn.disabled = busy;
    urlInput.disabled = busy;
    maxPagesInput.disabled = busy;
    btnSpinner.classList.toggle("d-none", !busy);
    btnIcon.classList.toggle("d-none", busy);
    btnText.textContent = busy ? "Đang thu thập..." : "Bắt đầu Crawl";
  }

  // ===== Chuyển đổi chế độ xem dữ liệu (Views) =====
  function switchView(viewName) {
    state.currentView = viewName;

    // Cập nhật pills active
    $("view-products-tab").classList.toggle("active", viewName === "products");
    $("view-links-tab").classList.toggle("active", viewName === "links");
    $("view-images-tab").classList.toggle("active", viewName === "images");
    $("view-raw-tab").classList.toggle("active", viewName === "raw");

    // Hiện / ẩn nội dung
    viewProductsContent.classList.toggle("d-none", viewName !== "products");
    viewLinksContent.classList.toggle("d-none", viewName !== "links");
    viewImagesContent.classList.toggle("d-none", viewName !== "images");
    viewRawContent.classList.toggle("d-none", viewName !== "raw");

    // Ẩn thanh lọc khi ở tab raw hoặc images/links nếu cần
    filterControlsArea.classList.toggle("d-none", viewName === "raw");

    if (viewName === "products") {
      renderProductsTable();
    } else if (viewName === "links") {
      renderLinksTable();
    } else if (viewName === "images") {
      renderImagesGrid();
    }
  }

  // ===== Hiển thị dữ liệu Sản phẩm (View 1) =====
  function getFilteredProducts() {
    const q = state.filterText.trim().toLowerCase();
    const ratingFilter = state.filterRating;
    const stockFilter = state.filterStock;

    let list = state.items.filter((it) => {
      // 1. Tìm kiếm văn bản
      if (q && !String(it.title || "").toLowerCase().includes(q)) return false;

      // 2. Lọc theo đánh giá sao
      if (ratingFilter !== "") {
        const expected = parseInt(ratingFilter, 10);
        if (Math.round(it.rating || 0) !== expected) return false;
      }

      // 3. Lọc theo tình trạng kho
      if (stockFilter === "in-stock") {
        if (!/in stock/i.test(it.availability || "")) return false;
      } else if (stockFilter === "out-of-stock") {
        if (/in stock/i.test(it.availability || "")) return false;
      }

      return true;
    });

    // Sắp xếp
    if (state.sortKey) {
      const key = state.sortKey;
      const dir = state.sortDir;
      list = list.slice().sort((a, b) => {
        const va = a[key];
        const vb = b[key];
        if (va == null && vb == null) return 0;
        if (va == null) return 1;
        if (vb == null) return -1;
        if (typeof va === "number" && typeof vb === "number") return (va - vb) * dir;
        return String(va).localeCompare(String(vb), "vi", { sensitivity: "base" }) * dir;
      });
    }

    return list;
  }

  function renderProductsTable() {
    const list = getFilteredProducts();
    countBadge.textContent = list.length === state.items.length
      ? String(list.length)
      : list.length + " / " + state.items.length;

    resultsBody.innerHTML = list.map((it, idx) => {
      const href = safeHttpUrl(it.url);
      const img = safeHttpUrl(it.image_url);
      const title = escapeHtml(it.title || "(không có tiêu đề)");
      const titleCell = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-medium text-dark">' + title + '</a>'
        : title;
      const linkBtn = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm py-0 px-1.5" title="Mở trang gốc"><i class="bi bi-box-arrow-up-right"></i></a>'
        : '<span class="text-muted">—</span>';
      const imgCell = img
        ? '<img class="prod-thumb" loading="lazy" src="' + escapeHtml(img) + '" alt="" referrerpolicy="no-referrer">'
        : '<div class="prod-thumb d-flex align-items-center justify-content-center text-muted small"><i class="bi bi-image"></i></div>';

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        "<td>" + imgCell + "</td>" +
        "<td>" + titleCell + "</td>" +
        '<td class="text-end fw-bold text-primary">' + escapeHtml(formatPrice(it)) + "</td>" +
        '<td class="text-center">' + starsHtml(it.rating) + "</td>" +
        '<td class="text-center">' + stockBadge(it.availability) + "</td>" +
        '<td class="text-center">' + linkBtn + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyFilter.classList.toggle("d-none", list.length > 0 || state.items.length === 0);

    // Cập nhật trạng thái sắp xếp cột
    document.querySelectorAll("#results-table th.sortable").forEach((th) => {
      const active = th.dataset.key === state.sortKey;
      th.classList.toggle("sort-active", active);
      const icon = th.querySelector(".sort-icon");
      if (icon) {
        icon.className = "bi sort-icon " + (active ? (state.sortDir === 1 ? "bi-sort-up text-primary" : "bi-sort-down text-primary") : "bi-arrow-down-up");
      }
    });
  }

  // ===== Hiển thị danh sách liên kết (View 2) =====
  function renderLinksTable() {
    const list = state.links || [];
    countBadge.textContent = String(list.length);

    linksTableBody.innerHTML = list.map((link, idx) => {
      const text = escapeHtml(link.text || "(không có văn bản)");
      const href = safeHttpUrl(link.url);
      const urlDisplay = escapeHtml(link.url || "");
      const linkBtn = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm py-0 px-1.5"><i class="bi bi-box-arrow-up-right"></i></a>'
        : '<span class="text-muted">—</span>';

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        '<td class="fw-medium text-dark">' + text + "</td>" +
        '<td><code class="small text-break">' + urlDisplay + "</code></td>" +
        '<td class="text-center">' + linkBtn + "</td>" +
        "</tr>"
      );
    }).join("");
  }

  // ===== Hiển thị bộ sưu tập ảnh (View 3) =====
  function renderImagesGrid() {
    const list = state.images || [];
    countBadge.textContent = String(list.length);

    if (list.length === 0) {
      imagesGridBody.innerHTML = '<div class="col-12 text-center text-muted py-4">Không tìm thấy hình ảnh nào.</div>';
      return;
    }

    imagesGridBody.innerHTML = list.map((img) => {
      const src = safeHttpUrl(img.src);
      const alt = escapeHtml(img.alt || "(không có mô tả alt)");
      const srcDisplay = escapeHtml(img.src || "");

      return (
        '<div class="image-grid-item">' +
        (src
          ? '<a href="' + escapeHtml(src) + '" target="_blank" rel="noopener noreferrer"><img src="' + escapeHtml(src) + '" alt="' + alt + '" loading="lazy" referrerpolicy="no-referrer"></a>'
          : '<div class="prod-thumb mb-2 mx-auto"></div>') +
        '<div class="image-grid-caption" title="' + alt + '">' + alt + "</div>" +
        '<div class="small text-muted text-truncate" title="' + srcDisplay + '"><code>' + srcDisplay + "</code></div>" +
        "</div>"
      );
    }).join("");
  }

  // ===== Tóm tắt kết quả =====
  function renderSummary(meta, elapsedSec) {
    statItems.textContent = state.items.length;
    statLinks.textContent = state.links.length;
    statImages.textContent = state.images.length;
    statTime.textContent = elapsedSec != null ? elapsedSec.toFixed(1) + "s" : "—";
    summary.classList.remove("d-none");
  }

  function renderWarnings(list) {
    if (!Array.isArray(list) || list.length === 0) {
      warningsBox.innerHTML = "";
      return;
    }
    warningsBox.innerHTML =
      '<div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle me-1"></i><strong>Lưu ý (' + list.length + "):</strong><ul class=\"mb-0 ps-3 mt-1\">" +
      list.map((w) => "<li>" + escapeHtml(w) + "</li>").join("") + "</ul></div>";
  }

  function showAllResults(data, elapsedSec) {
    state.items = Array.isArray(data.products) ? data.products : (Array.isArray(data.items) ? data.items : []);
    state.links = Array.isArray(data.links) ? data.links : [];
    state.images = Array.isArray(data.images) ? data.images : [];
    state.rawHtml = data.html || "";
    state.meta = data;

    // Reset filters
    state.filterText = "";
    state.filterRating = "";
    state.filterStock = "";
    filterInput.value = "";
    filterRating.value = "";
    filterStock.value = "";

    // Cập nhật số lượng trên các nút tab
    pillProdCount.textContent = state.items.length;
    pillLinkCount.textContent = state.links.length;
    pillImgCount.textContent = state.images.length;

    // Preview raw HTML
    rawHtmlPreview.textContent = state.rawHtml.slice(0, 15000) + (state.rawHtml.length > 15000 ? "\n\n... [Đã cắt bớt để tối ưu hiển thị, tổng độ dài: " + state.rawHtml.length + " ký tự]" : "");

    renderSummary(state.meta, elapsedSec);
    renderWarnings(state.meta.warnings);
    resultsCard.classList.remove("d-none");

    // Hiển thị view mặc định
    switchView("products");
  }

  // ===== Thực hiện quy trình Crawl =====
  async function executeCrawl(url, maxPages) {
    async function readJson(response) {
      const raw = await response.text();
      let data;
      try {
        data = JSON.parse(raw);
      } catch (e) {
        throw new Error("Backend PHP không trả về JSON hợp lệ: " + raw.slice(0, 120));
      }
      if (!response.ok || data.success === false) {
        const error = new Error(data.message || "Yêu cầu thất bại (HTTP " + response.status + ").");
        error.code = data.code || "HTTP_" + response.status;
        throw error;
      }
      return data;
    }

    // 1. Tải HTML qua backend cURL
    const crawlResponse = await fetch(API_CRAWL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
      body: new URLSearchParams({ url: url })
    });
    const crawlData = await readJson(crawlResponse);
    if (!crawlData.html) throw new Error("Website không trả về nội dung HTML.");

    // 2. Parse HTML qua backend DOMDocument + XPath
    const parseResponse = await fetch(API_PARSE, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
      body: new URLSearchParams({ html: crawlData.html, url: url })
    });
    const parsed = await readJson(parseResponse);

    // Chuẩn hóa đường dẫn sản phẩm & ảnh
    const resolvedProducts = (parsed.products || []).map((p) => ({
      ...p,
      url: resolveUrl(p.url, url),
      image_url: resolveUrl(p.image_url, url)
    }));

    const resolvedLinks = (parsed.links || []).map((l) => ({
      ...l,
      url: resolveUrl(l.url, url)
    }));

    const resolvedImages = (parsed.images || []).map((img) => ({
      ...img,
      src: resolveUrl(img.src, url)
    }));

    // 3. Tự động lưu vào database JSON
    try {
      await fetch(API_SAVE, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          url: url,
          html: crawlData.html,
          title: parsed.title || "",
          total_links: parsed.total_links || resolvedLinks.length,
          total_images: parsed.total_images || resolvedImages.length,
          links: resolvedLinks,
          images: resolvedImages,
          products: resolvedProducts
        })
      });
    } catch (saveErr) {
      console.warn("Không thể tự động lưu dữ liệu:", saveErr);
    }

    return {
      success: true,
      url: url,
      html: crawlData.html,
      title: parsed.title || "",
      products: resolvedProducts,
      links: resolvedLinks,
      images: resolvedImages,
      pages_crawled: 1,
      warnings: []
    };
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (state.busy) return;

    const url = urlInput.value.trim();
    const maxPages = parseInt(maxPagesInput.value, 10);

    let valid = true;
    urlInput.classList.toggle("is-invalid", !isValidUrl(url));
    if (!isValidUrl(url)) valid = false;

    if (!valid) {
      setStatus("warning", "Vui lòng nhập URL hợp lệ bắt đầu bằng http:// hoặc https://.");
      return;
    }

    setBusy(true);
    setStatus("info", "Đang gửi yêu cầu thu thập dữ liệu từ: " + url + " ...");
    const started = performance.now();

    try {
      const data = await executeCrawl(url, maxPages);
      const elapsed = (performance.now() - started) / 1000;

      showAllResults(data, elapsed);
      setStatus("success", "Thu thập thành công " + state.items.length + " sản phẩm, " + state.links.length + " liên kết trong " + elapsed.toFixed(1) + " giây.");
      loadSavedData();
    } catch (e) {
      setStatus("danger", e.message || "Đã xảy ra lỗi trong quá trình thu thập.", e.code);
    } finally {
      setBusy(false);
    }
  }

  // ===== Kiểm tra Robots.txt (Tab 2) =====
  async function handleRobotsCheck(event) {
    if (event) event.preventDefault();

    const targetUrl = robotsTestUrl.value.trim();
    const userAgent = robotsUserAgent.value.trim() || "*";

    if (!isValidUrl(targetUrl)) {
      alert("Vui lòng nhập URL hợp lệ để kiểm tra robots.txt");
      return;
    }

    btnCheckRobots.disabled = true;
    robotsSpinner.classList.remove("d-none");
    robotsIcon.classList.add("d-none");
    robotsResultBox.classList.remove("d-none");
    robotsStatusBadge.className = "badge text-bg-secondary";
    robotsStatusBadge.textContent = "ĐANG KIỂM TRA...";
    robotsStatusDesc.textContent = "Đang gửi yêu cầu tới website nguồn để tải và phân tích robots.txt...";
    robotsStatusMeta.innerHTML = "";

    try {
      const res = await fetch(API_ROBOTS, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
        body: new URLSearchParams({ url: targetUrl, user_agent: userAgent })
      });

      const data = await res.json();

      if (data.allowed) {
        robotsStatusBadge.className = "badge text-bg-success";
        robotsStatusBadge.textContent = "CHO PHÉP TRUY CẬP (ALLOWED)";
        robotsStatusTitle.textContent = "URL hợp lệ theo Robots.txt";
      } else {
        robotsStatusBadge.className = "badge text-bg-danger";
        robotsStatusBadge.textContent = "BỊ CHẶN TRUY CẬP (DISALLOWED)";
        robotsStatusTitle.textContent = "URL bị từ chối truy cập theo Robots.txt";
      }

      robotsStatusDesc.textContent = data.message || "";

      let metaHtml = "";
      if (data.robots_url) {
        metaHtml += '<div><i class="bi bi-link-45deg me-1"></i>Tệp robots.txt nguồn: <a href="' + escapeHtml(data.robots_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(data.robots_url) + '</a></div>';
      }
      if (data.matched_rule) {
        metaHtml += '<div class="mt-1"><i class="bi bi-code-square me-1"></i>Quy tắc khớp (Matched rule): <code>' + escapeHtml(data.matched_rule) + '</code></div>';
      }
      robotsStatusMeta.innerHTML = metaHtml;

    } catch (err) {
      robotsStatusBadge.className = "badge text-bg-warning";
      robotsStatusBadge.textContent = "LỖI KIỂM TRA";
      robotsStatusDesc.textContent = "Không thể kiểm tra robots.txt: " + err.message;
    } finally {
      btnCheckRobots.disabled = false;
      robotsSpinner.classList.add("d-none");
      robotsIcon.classList.remove("d-none");
    }
  }

  // ===== Quản lý CSDL & Lịch sử (Tab 3) =====
  async function loadSavedData() {
    try {
      const res = await fetch(API_RESULTS, { headers: { Accept: "application/json" } });
      if (!res.ok) return;
      const data = await res.json();

      const items = Array.isArray(data.items) ? data.items : [];
      const records = Array.isArray(data.records) ? data.records : [];

      state.savedRecords = records;
      savedSessionsCount.textContent = data.total_records != null ? data.total_records : records.length;
      savedTotalItems.textContent = items.length;

      // Render bảng lịch sử
      if (records.length === 0) {
        savedHistoryBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Chưa có phiên thu thập nào được lưu.</td></tr>';
        return;
      }

      savedHistoryBody.innerHTML = records.map((rec, idx) => {
        const time = escapeHtml(rec.saved_at || "—");
        const url = escapeHtml(rec.url || "");
        const title = escapeHtml(rec.title || "(không có tiêu đề)");
        const safeUrl = safeHttpUrl(rec.url);

        return (
          "<tr>" +
          '<td class="text-muted small">' + (idx + 1) + "</td>" +
          '<td class="small text-nowrap">' + time + "</td>" +
          '<td>' + (safeUrl ? '<a href="' + escapeHtml(safeUrl) + '" target="_blank" class="small text-break">' + url + '</a>' : url) + "</td>" +
          '<td class="fw-medium small">' + title + "</td>" +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_links || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_images || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-primary">' + (rec.total_products || 0) + '</span></td>' +
          "</tr>"
        );
      }).join("");

    } catch (err) {
      console.warn("Không tải được dữ liệu đã lưu:", err);
    }
  }

  async function handleViewSavedItems() {
    try {
      const res = await fetch(API_RESULTS, { headers: { Accept: "application/json" } });
      if (!res.ok) throw new Error("Lỗi mạng khi tải dữ liệu.");
      const data = await res.json();
      const items = Array.isArray(data.items) ? data.items : [];

      if (items.length === 0) {
        alert("Chưa có sản phẩm nào trong cơ sở dữ liệu.");
        return;
      }

      showAllResults({ products: items, links: [], images: [], html: "" }, 0);
      setStatus("info", "Đang hiển thị " + items.length + " sản phẩm được trích xuất từ cơ sở dữ liệu đã lưu.");

      // Chuyển sang Tab 1
      const triggerEl = document.querySelector('#crawler-tab');
      if (triggerEl && window.bootstrap) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
      }
    } catch (e) {
      alert("Không thể tải dữ liệu đã lưu: " + e.message);
    }
  }

  // ===== Xuất dữ liệu (Export) =====
  function downloadFile(filename, mime, content) {
    const blob = new Blob([content], { type: mime });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  function getTimestamp() {
    const d = new Date();
    const p = (n) => String(n).padStart(2, "0");
    return d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + "_" + p(d.getHours()) + p(d.getMinutes());
  }

  function exportJson() {
    let exportData = [];
    let filename = "crawl_data_";

    if (state.currentView === "products") {
      exportData = getFilteredProducts();
      filename += "products_";
    } else if (state.currentView === "links") {
      exportData = state.links;
      filename += "links_";
    } else if (state.currentView === "images") {
      exportData = state.images;
      filename += "images_";
    } else {
      exportData = { products: state.items, links: state.links, images: state.images };
      filename += "full_";
    }

    if (!Array.isArray(exportData) && typeof exportData !== "object") return;
    downloadFile(filename + getTimestamp() + ".json", "application/json;charset=utf-8", JSON.stringify(exportData, null, 2));
  }

  function csvCell(value) {
    let s = value == null ? "" : String(value);
    if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; // Chống CSV / Formula injection
    return '"' + s.replace(/"/g, '""') + '"';
  }

  function exportCsv() {
    let cols = [];
    let list = [];
    let filename = "crawl_data_";

    if (state.currentView === "products") {
      cols = ["title", "url", "price", "currency", "rating", "availability", "image_url"];
      list = getFilteredProducts();
      filename += "products_";
    } else if (state.currentView === "links") {
      cols = ["text", "url"];
      list = state.links;
      filename += "links_";
    } else if (state.currentView === "images") {
      cols = ["alt", "src"];
      list = state.images;
      filename += "images_";
    } else {
      cols = ["title", "url", "price", "currency", "rating", "availability", "image_url"];
      list = state.items;
      filename += "products_";
    }

    if (!list.length) {
      alert("Không có dữ liệu để xuất CSV.");
      return;
    }

    const lines = [cols.join(",")].concat(
      list.map((it) => cols.map((c) => csvCell(it[c])).join(","))
    );

    // Dùng UTF-8 BOM (\uFEFF) để Excel hiển thị tiếng Việt chuẩn xác
    downloadFile(filename + getTimestamp() + ".csv", "text/csv;charset=utf-8", "\uFEFF" + lines.join("\r\n"));
  }

  function copyRawHtml() {
    if (!state.rawHtml) {
      alert("Chưa có mã HTML để sao chép.");
      return;
    }
    navigator.clipboard.writeText(state.rawHtml).then(() => {
      const btn = $("btn-copy-html");
      const oldHtml = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-check-lg me-1 text-success"></i>Đã sao chép!';
      setTimeout(() => { btn.innerHTML = oldHtml; }, 2000);
    });
  }

  // ===== Gán sự kiện (Event Listeners) =====
  form.addEventListener("submit", handleSubmit);
  urlInput.addEventListener("input", () => urlInput.classList.remove("is-invalid"));
  maxPagesInput.addEventListener("input", () => maxPagesInput.classList.remove("is-invalid"));

  // Lọc tìm kiếm tiêu đề
  filterInput.addEventListener("input", () => {
    state.filterText = filterInput.value;
    renderProductsTable();
  });

  // Lọc đánh giá sao
  filterRating.addEventListener("change", () => {
    state.filterRating = filterRating.value;
    renderProductsTable();
  });

  // Lọc tình trạng kho
  filterStock.addEventListener("change", () => {
    state.filterStock = filterStock.value;
    renderProductsTable();
  });

  // Reset bộ lọc
  btnResetFilter.addEventListener("click", () => {
    state.filterText = "";
    state.filterRating = "";
    state.filterStock = "";
    filterInput.value = "";
    filterRating.value = "";
    filterStock.value = "";
    renderProductsTable();
  });

  // Nguồn thử nghiệm mẫu
  document.querySelectorAll(".example-url").forEach((btn) => {
    btn.addEventListener("click", () => {
      urlInput.value = btn.dataset.url;
      urlInput.classList.remove("is-invalid");
      urlInput.focus();
    });
  });

  // Chuyển nhanh sang kiểm tra robots.txt
  quickCheckRobotsLink.addEventListener("click", (e) => {
    e.preventDefault();
    const currentUrl = urlInput.value.trim() || "https://books.toscrape.com/";
    robotsTestUrl.value = currentUrl;
    const triggerEl = document.querySelector('#robots-tab');
    if (triggerEl && window.bootstrap) {
      const tab = new bootstrap.Tab(triggerEl);
      tab.show();
    }
    handleRobotsCheck();
  });

  // Chuyển đổi View sub-tabs
  $("view-products-tab").addEventListener("click", () => switchView("products"));
  $("view-links-tab").addEventListener("click", () => switchView("links"));
  $("view-images-tab").addEventListener("click", () => switchView("images"));
  $("view-raw-tab").addEventListener("click", () => switchView("raw"));

  // Sắp xếp cột bảng sản phẩm
  document.querySelectorAll("#results-table th.sortable").forEach((th) => {
    th.addEventListener("click", () => {
      const key = th.dataset.key;
      if (state.sortKey === key) {
        state.sortDir = -state.sortDir;
      } else {
        state.sortKey = key;
        state.sortDir = 1;
      }
      renderProductsTable();
    });
  });

  // Xuất file
  $("export-json").addEventListener("click", exportJson);
  $("export-csv").addEventListener("click", exportCsv);
  $("btn-copy-html").addEventListener("click", copyRawHtml);

  // Tab Robots.txt
  robotsForm.addEventListener("submit", handleRobotsCheck);
  document.querySelectorAll(".robots-preset").forEach((btn) => {
    btn.addEventListener("click", () => {
      robotsTestUrl.value = btn.dataset.url;
      handleRobotsCheck();
    });
  });

  // Tab CSDL
  btnRefreshSaved.addEventListener("click", loadSavedData);
  btnViewSavedItems.addEventListener("click", handleViewSavedItems);

  // Khởi động
  loadSavedData();
})();
