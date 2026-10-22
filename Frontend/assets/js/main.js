/**
 * Web Data Crawler - Frontend Logic (Sinh viên Năm 3)
 * Phục vụ đồ án môn học Web Crawling & Scraping
 * Hỗ trợ đa dạng nguồn dữ liệu: Website Tin tức / Blog, Tệp tài liệu, E-Commerce
 * Bộ lọc trích xuất chuyên sâu: Tiêu đề (Headings), Liên kết, Hình ảnh, Tệp tin
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
  const crawlModeSelect = $("crawl-mode");
  const maxPagesInput = $("max-pages");
  const customXpathInput = $("custom-xpath");
  const crawlBtn = $("crawl-btn");
  const btnSpinner = $("btn-spinner");
  const btnIcon = $("btn-icon");
  const btnText = $("btn-text");
  const quickCheckRobotsLink = $("quick-check-robots-link");

  // Status & Summary
  const statusArea = $("status-area");
  const summary = $("summary");
  const statArticles = $("stat-articles");
  const statHeadings = $("stat-headings");
  const statFiles = $("stat-files");
  const statItems = $("stat-items");
  const statLinks = $("stat-links");
  const statImages = $("stat-images");

  // Results & View Switcher
  const resultsCard = $("results-card");
  const countBadge = $("count-badge");
  const warningsBox = $("warnings");

  const pillArtCount = $("pill-art-count");
  const pillHeadCount = $("pill-head-count");
  const pillFileCount = $("pill-file-count");
  const pillProdCount = $("pill-prod-count");
  const pillLinkCount = $("pill-link-count");
  const pillImgCount = $("pill-img-count");

  const viewArticlesContent = $("view-articles-content");
  const viewHeadingsContent = $("view-headings-content");
  const viewFilesContent = $("view-files-content");
  const viewProductsContent = $("view-products-content");
  const viewLinksContent = $("view-links-content");
  const viewImagesContent = $("view-images-content");
  const viewRawContent = $("view-raw-content");

  // Tables & Bodies
  const articlesTableBody = $("articles-table-body");
  const headingsTableBody = $("headings-table-body");
  const filesTableBody = $("files-table-body");
  const resultsBody = $("results-body");
  const linksTableBody = $("links-table-body");
  const imagesGridBody = $("images-grid-body");
  const rawHtmlPreview = $("raw-html-preview");

  // Empty Alerts
  const emptyArticles = $("empty-articles");
  const emptyHeadings = $("empty-headings");
  const emptyFiles = $("empty-files");
  const emptyFilter = $("empty-filter");
  const emptyLinks = $("empty-links");
  const emptyImages = $("empty-images");

  // Filters Elements
  const filterArtInput = $("filter-art-input");
  const btnResetArtFilter = $("btn-reset-art-filter");

  const filterHeadingInput = $("filter-heading-input");
  const filterHeadingLevel = $("filter-heading-level");
  const btnResetHeadFilter = $("btn-reset-head-filter");

  const filterFileInput = $("filter-file-input");
  const filterFileExt = $("filter-file-ext");
  const btnResetFileFilter = $("btn-reset-file-filter");

  const filterInput = $("filter-input");
  const filterRating = $("filter-rating");
  const filterStock = $("filter-stock");
  const btnResetFilter = $("btn-reset-filter");

  const filterLinkInput = $("filter-link-input");
  const filterLinkType = $("filter-link-type");
  const btnResetLinkFilter = $("btn-reset-link-filter");

  const filterImgInput = $("filter-img-input");
  const filterImgExt = $("filter-img-ext");
  const btnResetImgFilter = $("btn-reset-img-filter");

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
  const btnClearSaved = $("btn-clear-saved");

  // ===== Trạng thái ứng dụng (Application State) =====
  const state = {
    currentView: "articles", // 'articles' | 'headings' | 'files' | 'products' | 'links' | 'images' | 'raw'
    articles: [],            // Danh sách bài viết blog
    headings: [],            // Cây tiêu đề H1-H6
    files: [],               // Danh sách tệp tài liệu
    items: [],               // Danh sách sản phẩm (Books to Scrape)
    links: [],               // Danh sách liên kết
    images: [],              // Danh sách hình ảnh
    rawHtml: "",             // HTML thô
    
    // Filters
    filterArtText: "",
    filterHeadText: "",
    filterHeadLevel: "",
    filterFileText: "",
    filterFileExt: "",
    filterText: "",
    filterRating: "",
    filterStock: "",
    filterLinkText: "",
    filterLinkType: "",
    filterImgText: "",
    filterImgExt: "",

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
        ? '<i class="bi bi-star-fill text-warning"></i>'
        : '<i class="bi bi-star-fill text-muted opacity-25"></i>';
    }
    return out + "</span>";
  }

  function stockBadge(text) {
    if (!text) return '<span class="text-muted">—</span>';
    const ok = /in stock/i.test(text);
    return '<span class="badge ' + (ok ? "bg-success-subtle text-success border border-success-subtle" : "bg-danger-subtle text-danger border border-danger-subtle") + '">' + escapeHtml(text) + "</span>";
  }

  function fileBadge(ext) {
    const e = String(ext || "").toLowerCase();
    const colors = {
      pdf: "danger",
      doc: "primary",
      docx: "primary",
      xls: "success",
      xlsx: "success",
      csv: "success",
      zip: "warning",
      rar: "warning",
      txt: "secondary"
    };
    const c = colors[e] || "dark";
    return '<span class="badge text-bg-' + c + ' text-uppercase font-monospace px-2 py-1">' + escapeHtml(e || "FILE") + "</span>";
  }

  function headingBadge(tag) {
    const t = String(tag || "h2").toLowerCase();
    const colors = {
      h1: "danger",
      h2: "primary",
      h3: "info",
      h4: "secondary",
      h5: "light text-dark border",
      h6: "light text-dark border"
    };
    const c = colors[t] || "secondary";
    return '<span class="badge text-bg-' + c + ' text-uppercase font-monospace">' + escapeHtml(t) + "</span>";
  }

  // ===== Trạng thái giao diện =====
  function setStatus(type, message, code) {
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
    crawlModeSelect.disabled = busy;
    btnSpinner.classList.toggle("d-none", !busy);
    btnIcon.classList.toggle("d-none", busy);
    btnText.textContent = busy ? "Đang thu thập..." : "Bắt đầu Crawl";
  }

  // ===== Chuyển đổi chế độ xem dữ liệu (Views) =====
  function switchView(viewName) {
    state.currentView = viewName;

    // Cập nhật pills active
    $("view-articles-tab").classList.toggle("active", viewName === "articles");
    $("view-headings-tab").classList.toggle("active", viewName === "headings");
    $("view-files-tab").classList.toggle("active", viewName === "files");
    $("view-products-tab").classList.toggle("active", viewName === "products");
    $("view-links-tab").classList.toggle("active", viewName === "links");
    $("view-images-tab").classList.toggle("active", viewName === "images");
    $("view-raw-tab").classList.toggle("active", viewName === "raw");

    // Hiện / ẩn nội dung
    viewArticlesContent.classList.toggle("d-none", viewName !== "articles");
    viewHeadingsContent.classList.toggle("d-none", viewName !== "headings");
    viewFilesContent.classList.toggle("d-none", viewName !== "files");
    viewProductsContent.classList.toggle("d-none", viewName !== "products");
    viewLinksContent.classList.toggle("d-none", viewName !== "links");
    viewImagesContent.classList.toggle("d-none", viewName !== "images");
    viewRawContent.classList.toggle("d-none", viewName !== "raw");

    if (viewName === "articles") renderArticlesTable();
    else if (viewName === "headings") renderHeadingsTable();
    else if (viewName === "files") renderFilesTable();
    else if (viewName === "products") renderProductsTable();
    else if (viewName === "links") renderLinksTable();
    else if (viewName === "images") renderImagesGrid();
  }

  // ===== VIEW 0: BÀI VIẾT BLOG & TIN TỨC =====
  function getFilteredArticles() {
    const q = state.filterArtText.trim().toLowerCase();
    if (!q) return state.articles || [];
    return (state.articles || []).filter((art) => {
      const title = String(art.title || "").toLowerCase();
      const author = String(art.author || "").toLowerCase();
      const excerpt = String(art.excerpt || "").toLowerCase();
      return title.includes(q) || author.includes(q) || excerpt.includes(q);
    });
  }

  function renderArticlesTable() {
    const list = getFilteredArticles();
    countBadge.textContent = list.length === state.articles.length
      ? String(list.length)
      : list.length + " / " + state.articles.length;

    articlesTableBody.innerHTML = list.map((art, idx) => {
      const href = safeHttpUrl(art.url);
      const img = safeHttpUrl(art.image_url);
      const title = escapeHtml(art.title || "(không có tiêu đề)");
      const author = escapeHtml(art.author || "—");
      const date = escapeHtml(art.date || "—");
      const excerpt = escapeHtml(art.excerpt || "(không có tóm tắt)");

      const titleCell = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold text-primary">' + title + '</a>'
        : title;
      const linkBtn = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm py-0 px-1.5" title="Mở bài gốc"><i class="bi bi-box-arrow-up-right"></i></a>'
        : '<span class="text-muted">—</span>';
      const imgCell = img
        ? '<img class="prod-thumb rounded" loading="lazy" src="' + escapeHtml(img) + '" alt="" referrerpolicy="no-referrer">'
        : '<div class="prod-thumb rounded d-flex align-items-center justify-content-center text-muted small bg-light"><i class="bi bi-journal-text fs-5"></i></div>';

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        "<td>" + imgCell + "</td>" +
        "<td>" + titleCell + "</td>" +
        '<td class="small text-secondary"><i class="bi bi-person me-1"></i>' + author + "</td>" +
        '<td class="small text-secondary"><i class="bi bi-calendar3 me-1"></i>' + date + "</td>" +
        '<td class="small text-muted text-break" style="max-width: 320px;">' + excerpt + "</td>" +
        '<td class="text-center">' + linkBtn + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyArticles.classList.toggle("d-none", list.length > 0);
  }

  // ===== VIEW 1: CÂY TIÊU ĐỀ HEADINGS =====
  function getFilteredHeadings() {
    const q = state.filterHeadText.trim().toLowerCase();
    const lvl = state.filterHeadLevel;
    return (state.headings || []).filter((h) => {
      if (q && !String(h.text || "").toLowerCase().includes(q)) return false;
      if (lvl === "h1" && h.tag !== "h1") return false;
      if (lvl === "h2" && h.tag !== "h2") return false;
      if (lvl === "h3" && h.tag !== "h3") return false;
      if (lvl === "h4-h6" && !["h4", "h5", "h6"].includes(h.tag)) return false;
      return true;
    });
  }

  function renderHeadingsTable() {
    const list = getFilteredHeadings();
    countBadge.textContent = list.length === state.headings.length
      ? String(list.length)
      : list.length + " / " + state.headings.length;

    headingsTableBody.innerHTML = list.map((h, idx) => {
      const badge = headingBadge(h.tag);
      const text = escapeHtml(h.text);
      const hid = h.id ? '<code>#' + escapeHtml(h.id) + '</code>' : '<span class="text-muted">—</span>';
      const indent = (h.level - 1) * 16;

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        '<td>' + badge + "</td>" +
        '<td><div style="padding-left: ' + indent + 'px;" class="fw-medium text-dark">' + text + '</div></td>' +
        '<td>' + hid + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyHeadings.classList.toggle("d-none", list.length > 0);
  }

  // ===== VIEW 2: TỆP TIN & TÀI LIỆU =====
  function getFilteredFiles() {
    const q = state.filterFileText.trim().toLowerCase();
    const ext = state.filterFileExt.toLowerCase();

    return (state.files || []).filter((f) => {
      const name = String(f.name || "").toLowerCase();
      const text = String(f.anchor_text || "").toLowerCase();
      if (q && !name.includes(q) && !text.includes(q)) return false;

      if (ext) {
        const fExt = String(f.extension || "").toLowerCase();
        if (ext === "doc" && !["doc", "docx"].includes(fExt)) return false;
        if (ext === "xls" && !["xls", "xlsx", "csv"].includes(fExt)) return false;
        if (ext === "zip" && !["zip", "rar", "7z", "tar", "gz"].includes(fExt)) return false;
        if (ext === "pdf" && fExt !== "pdf") return false;
        if (ext === "txt" && fExt !== "txt") return false;
      }
      return true;
    });
  }

  function renderFilesTable() {
    const list = getFilteredFiles();
    countBadge.textContent = list.length === state.files.length
      ? String(list.length)
      : list.length + " / " + state.files.length;

    filesTableBody.innerHTML = list.map((f, idx) => {
      const href = safeHttpUrl(f.url);
      const badge = fileBadge(f.extension);
      const name = escapeHtml(f.name || "Tệp không tên");
      const anchor = escapeHtml(f.anchor_text || "—");
      const isInternal = f.is_internal
        ? '<span class="badge bg-light text-dark border">Nội bộ</span>'
        : '<span class="badge bg-light text-secondary border">Bên ngoài</span>';

      const downloadBtn = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" download class="btn btn-outline-primary btn-sm py-0 px-2" title="Tải hoặc mở tệp">' +
          '<i class="bi bi-download me-1"></i>Tải về</a>'
        : '<span class="text-muted">—</span>';

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        '<td class="text-center">' + badge + "</td>" +
        '<td><div class="fw-semibold text-break">' + name + '</div><small class="text-muted font-monospace">' + escapeHtml(f.url) + '</small></td>' +
        '<td class="small text-secondary">' + anchor + "</td>" +
        '<td class="text-center">' + isInternal + "</td>" +
        '<td class="text-center">' + downloadBtn + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyFiles.classList.toggle("d-none", list.length > 0);
  }

  // ===== VIEW 3: SẢN PHẨM (BOOKS TO SCRAPE) =====
  function getFilteredProducts() {
    const q = state.filterText.trim().toLowerCase();
    const ratingFilter = state.filterRating;
    const stockFilter = state.filterStock;

    let list = state.items.filter((it) => {
      if (q && !String(it.title || "").toLowerCase().includes(q)) return false;
      if (ratingFilter !== "") {
        const expected = parseInt(ratingFilter, 10);
        if (Math.round(it.rating || 0) !== expected) return false;
      }
      if (stockFilter === "in-stock") {
        if (!/in stock/i.test(it.availability || "")) return false;
      } else if (stockFilter === "out-of-stock") {
        if (/in stock/i.test(it.availability || "")) return false;
      }
      return true;
    });

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

    document.querySelectorAll("#results-table th.sortable").forEach((th) => {
      const active = th.dataset.key === state.sortKey;
      th.classList.toggle("sort-active", active);
      const icon = th.querySelector(".sort-icon");
      if (icon) {
        icon.className = "bi sort-icon " + (active ? (state.sortDir === 1 ? "bi-sort-up text-primary" : "bi-sort-down text-primary") : "bi-arrow-down-up");
      }
    });
  }

  // ===== VIEW 4: LIÊN KẾT (LINKS) =====
  function getFilteredLinks() {
    const q = state.filterLinkText.trim().toLowerCase();
    const type = state.filterLinkType;

    return (state.links || []).filter((l) => {
      const text = String(l.text || "").toLowerCase();
      const url = String(l.url || "").toLowerCase();
      if (q && !text.includes(q) && !url.includes(q)) return false;

      if (type === "internal" && !l.is_internal) return false;
      if (type === "external" && l.is_internal) return false;
      if (type === "file" && l.type !== "file") return false;
      return true;
    });
  }

  function renderLinksTable() {
    const list = getFilteredLinks();
    countBadge.textContent = list.length === state.links.length
      ? String(list.length)
      : list.length + " / " + state.links.length;

    linksTableBody.innerHTML = list.map((link, idx) => {
      const text = escapeHtml(link.text || "(không có văn bản)");
      const href = safeHttpUrl(link.url);
      const urlDisplay = escapeHtml(link.url || "");
      const linkBtn = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm py-0 px-1.5"><i class="bi bi-box-arrow-up-right"></i></a>'
        : '<span class="text-muted">—</span>';

      let scopeBadge = link.is_internal
        ? '<span class="badge bg-light text-primary border">Nội bộ</span>'
        : '<span class="badge bg-light text-secondary border">Liên kết ngoài</span>';
      if (link.type === "file") {
        scopeBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border">Tệp tải về</span>';
      }

      return (
        "<tr>" +
        '<td class="text-muted small">' + (idx + 1) + "</td>" +
        '<td class="text-center">' + scopeBadge + "</td>" +
        '<td class="fw-medium text-dark">' + text + "</td>" +
        '<td><code class="small text-break">' + urlDisplay + "</code></td>" +
        '<td class="text-center">' + linkBtn + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyLinks.classList.toggle("d-none", list.length > 0);
  }

  // ===== VIEW 5: HÌNH ẢNH (IMAGES) =====
  function getFilteredImages() {
    const q = state.filterImgText.trim().toLowerCase();
    const ext = state.filterImgExt.toLowerCase();

    return (state.images || []).filter((img) => {
      const alt = String(img.alt || "").toLowerCase();
      const src = String(img.src || "").toLowerCase();
      if (q && !alt.includes(q) && !src.includes(q)) return false;

      if (ext) {
        const iExt = String(img.extension || "").toLowerCase();
        if (ext === "jpg" && !["jpg", "jpeg"].includes(iExt)) return false;
        if (ext === "png" && iExt !== "png") return false;
        if (ext === "webp" && iExt !== "webp") return false;
        if (ext === "svg" && iExt !== "svg") return false;
      }
      return true;
    });
  }

  function renderImagesGrid() {
    const list = getFilteredImages();
    countBadge.textContent = list.length === state.images.length
      ? String(list.length)
      : list.length + " / " + state.images.length;

    if (list.length === 0) {
      imagesGridBody.innerHTML = "";
      emptyImages.classList.remove("d-none");
      return;
    }
    emptyImages.classList.add("d-none");

    imagesGridBody.innerHTML = list.map((img) => {
      const src = safeHttpUrl(img.src);
      const alt = escapeHtml(img.alt || "(không có mô tả alt)");
      const srcDisplay = escapeHtml(img.src || "");
      const ext = escapeHtml(String(img.extension || "ảnh").toUpperCase());

      return (
        '<div class="image-grid-item position-relative">' +
        '<span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 end-0 m-2 small font-monospace">' + ext + '</span>' +
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
    statArticles.textContent = state.articles.length;
    statHeadings.textContent = state.headings.length;
    statFiles.textContent = state.files.length;
    statItems.textContent = state.items.length;
    statLinks.textContent = state.links.length;
    statImages.textContent = state.images.length;
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
    state.articles = Array.isArray(data.articles) ? data.articles : [];
    state.headings = Array.isArray(data.headings) ? data.headings : [];
    state.files = Array.isArray(data.files) ? data.files : [];
    state.items = Array.isArray(data.products) ? data.products : (Array.isArray(data.items) ? data.items : []);
    state.links = Array.isArray(data.links) ? data.links : [];
    state.images = Array.isArray(data.images) ? data.images : [];
    state.rawHtml = data.html || "";
    state.meta = data;

    // Cập nhật số lượng trên các nút tab
    pillArtCount.textContent = state.articles.length;
    pillHeadCount.textContent = state.headings.length;
    pillFileCount.textContent = state.files.length;
    pillProdCount.textContent = state.items.length;
    pillLinkCount.textContent = state.links.length;
    pillImgCount.textContent = state.images.length;

    // Preview raw HTML
    rawHtmlPreview.textContent = state.rawHtml.slice(0, 15000) + (state.rawHtml.length > 15000 ? "\n\n... [Đã cắt bớt để tối ưu hiển thị, tổng độ dài: " + state.rawHtml.length + " ký tự]" : "");

    renderSummary(state.meta, elapsedSec);
    renderWarnings(state.meta.warnings);
    resultsCard.classList.remove("d-none");

    // Chọn view thông minh dựa trên chế độ trích xuất hoặc kết quả tìm thấy
    const mode = crawlModeSelect.value;
    if (mode === "blog") switchView("articles");
    else if (mode === "headings") switchView("headings");
    else if (mode === "files") switchView("files");
    else if (mode === "products") switchView("products");
    else if (mode === "links") switchView("links");
    else if (mode === "images") switchView("images");
    else {
      // Auto mode
      if (state.articles.length > 0) switchView("articles");
      else if (state.items.length > 0) switchView("products");
      else if (state.files.length > 0) switchView("files");
      else if (state.headings.length > 0) switchView("headings");
      else switchView("links");
    }
  }

  // ===== Thực hiện quy trình Crawl Đa Trang (Multi-page Crawler) =====
  async function executeCrawl(startUrl, maxPages, customXpath) {
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

    let currentUrl = startUrl;
    let pageCount = 0;
    const visitedUrls = new Set();

    let allProducts = [];
    let allArticles = [];
    let allHeadings = [];
    let allFiles = [];
    let allLinks = [];
    let allImages = [];
    let lastHtml = "";
    let mainTitle = "";
    let warnings = [];

    while (currentUrl && pageCount < maxPages) {
      if (visitedUrls.has(currentUrl)) break;
      visitedUrls.add(currentUrl);
      pageCount++;

      setStatus("info", "Đang thu thập trang " + pageCount + "/" + maxPages + ": " + currentUrl + " ...");

      // 1. Tải HTML / Tệp qua backend cURL
      const crawlResponse = await fetch(API_CRAWL, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
        body: new URLSearchParams({ url: currentUrl })
      });
      const crawlData = await readJson(crawlResponse);

      // Nếu URL trực tiếp là tệp tài liệu (không phải HTML)
      if (crawlData.is_file) {
        const fileExt = (currentUrl.split('.').pop() || 'file').split(/[?#]/)[0];
        allFiles.push({
          name: currentUrl.split('/').pop() || "Tệp tải về",
          url: currentUrl,
          extension: fileExt,
          anchor_text: "Liên kết tệp trực tiếp",
          is_internal: true
        });
        lastHtml = crawlData.raw_content || "[Nội dung tệp nhị phân / tệp văn bản]";
        break;
      }

      if (!crawlData.html) throw new Error("Website không trả về nội dung HTML.");
      lastHtml = crawlData.html;

      // 2. Parse HTML qua backend DOMDocument + XPath
      const parseParams = new URLSearchParams({ html: crawlData.html, url: currentUrl });
      if (customXpath) parseParams.append("custom_xpath", customXpath);

      const parseResponse = await fetch(API_PARSE, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
        body: parseParams
      });
      const parsed = await readJson(parseResponse);

      if (!mainTitle && parsed.title) mainTitle = parsed.title;

      // Gộp các sản phẩm
      if (Array.isArray(parsed.products)) {
        parsed.products.forEach((p) => {
          if (!allProducts.some((x) => x.url === p.url)) allProducts.push(p);
        });
      }

      // Gộp các bài viết blog
      if (Array.isArray(parsed.articles)) {
        parsed.articles.forEach((a) => {
          if (!allArticles.some((x) => x.url === a.url)) allArticles.push(a);
        });
      }

      // Gộp cây tiêu đề
      if (Array.isArray(parsed.headings)) {
        parsed.headings.forEach((h) => allHeadings.push(h));
      }

      // Gộp các tệp tài liệu
      if (Array.isArray(parsed.files)) {
        parsed.files.forEach((f) => {
          if (!allFiles.some((x) => x.url === f.url)) allFiles.push(f);
        });
      }

      // Gộp liên kết
      if (Array.isArray(parsed.links)) {
        parsed.links.forEach((l) => {
          if (!allLinks.some((x) => x.url === l.url)) allLinks.push(l);
        });
      }

      // Gộp hình ảnh
      if (Array.isArray(parsed.images)) {
        parsed.images.forEach((img) => {
          if (!allImages.some((x) => x.src === img.src)) allImages.push(img);
        });
      }

      // Kiểm tra có trang tiếp theo không
      if (pageCount < maxPages && parsed.next_page_url && !visitedUrls.has(parsed.next_page_url)) {
        currentUrl = parsed.next_page_url;
        // Độ trễ lịch sự 800ms giữa các trang
        await new Promise((r) => setTimeout(r, 800));
      } else {
        break;
      }
    }

    // 3. Tự động lưu vào database JSON
    try {
      await fetch(API_SAVE, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          url: startUrl,
          html: lastHtml,
          title: mainTitle || "Kết quả thu thập",
          total_links: allLinks.length,
          total_images: allImages.length,
          total_headings: allHeadings.length,
          total_articles: allArticles.length,
          total_files: allFiles.length,
          headings: allHeadings,
          articles: allArticles,
          files: allFiles,
          links: allLinks,
          images: allImages,
          products: allProducts
        })
      });
    } catch (saveErr) {
      console.warn("Không thể tự động lưu dữ liệu:", saveErr);
    }

    return {
      success: true,
      url: startUrl,
      html: lastHtml,
      title: mainTitle,
      articles: allArticles,
      headings: allHeadings,
      files: allFiles,
      products: allProducts,
      links: allLinks,
      images: allImages,
      pages_crawled: pageCount,
      warnings: warnings
    };
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (state.busy) return;

    const url = urlInput.value.trim();
    const maxPages = Math.min(Math.max(parseInt(maxPagesInput.value, 10) || 1, 1), 10);
    const customXpath = (customXpathInput.value || "").trim();

    let valid = true;
    urlInput.classList.toggle("is-invalid", !isValidUrl(url));
    if (!isValidUrl(url)) valid = false;

    if (!valid) {
      setStatus("warning", "Vui lòng nhập URL hợp lệ bắt đầu bằng http:// hoặc https://.");
      return;
    }

    setBusy(true);
    setStatus("info", "Đang khởi tạo phiên thu thập dữ liệu...");
    const started = performance.now();

    try {
      const data = await executeCrawl(url, maxPages, customXpath);
      const elapsed = (performance.now() - started) / 1000;

      showAllResults(data, elapsed);

      let msg = "Thu thập thành công " + (data.pages_crawled || 1) + " trang: ";
      const parts = [];
      if (data.articles.length > 0) parts.push(data.articles.length + " bài viết blog");
      if (data.files.length > 0) parts.push(data.files.length + " tệp tin");
      if (data.headings.length > 0) parts.push(data.headings.length + " tiêu đề");
      if (data.products.length > 0) parts.push(data.products.length + " sản phẩm");
      parts.push(data.links.length + " liên kết");
      parts.push(data.images.length + " hình ảnh");

      msg += parts.join(", ") + " trong " + elapsed.toFixed(1) + " giây.";
      setStatus("success", msg);
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
        savedHistoryBody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">Chưa có phiên thu thập nào được lưu.</td></tr>';
        return;
      }

      savedHistoryBody.innerHTML = records.map((rec, idx) => {
        const time = escapeHtml(rec.saved_at || "—");
        const url = escapeHtml(rec.url || "");
        const title = escapeHtml(rec.title || "(không có tiêu đề)");
        const safeUrl = safeHttpUrl(rec.url);
        const recordIndex = rec.index != null ? rec.index : (rec.id - 1);

        return (
          "<tr>" +
          '<td class="text-muted small">' + (idx + 1) + "</td>" +
          '<td class="small text-nowrap">' + time + "</td>" +
          '<td>' + (safeUrl ? '<a href="' + escapeHtml(safeUrl) + '" target="_blank" class="small text-break">' + url + '</a>' : url) + "</td>" +
          '<td class="fw-medium small">' + title + "</td>" +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_articles || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_files || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_links || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-light border">' + (rec.total_images || 0) + '</span></td>' +
          '<td class="text-center small"><span class="badge text-bg-primary">' + (rec.total_products || 0) + '</span></td>' +
          '<td class="text-center small">' +
          '<button class="btn btn-outline-danger btn-sm py-0 px-1.5 btn-del-record" data-index="' + recordIndex + '" title="Xóa bản ghi này"><i class="bi bi-trash3"></i></button>' +
          "</td>" +
          "</tr>"
        );
      }).join("");

      // Gán sự kiện xóa cho các nút
      document.querySelectorAll(".btn-del-record").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
          const idx = btn.dataset.index;
          if (!confirm("Bạn có chắc chắn muốn xóa bản ghi này?")) return;
          try {
            const delRes = await fetch(API_SAVE, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ action: "delete", record_id: idx })
            });
            const delData = await delRes.json();
            if (delData.success) {
              loadSavedData();
            } else {
              alert(delData.message || "Xóa thất bại.");
            }
          } catch (err) {
            alert("Lỗi kết nối khi xóa: " + err.message);
          }
        });
      });

    } catch (err) {
      console.warn("Không tải được dữ liệu đã lưu:", err);
    }
  }

  async function handleClearAllSaved() {
    if (!confirm("Bạn có chắc chắn muốn XÓA TOÀN BỘ lịch sử thu thập dữ liệu không?")) return;
    try {
      const res = await fetch(API_SAVE, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "clear" })
      });
      const data = await res.json();
      if (data.success) {
        alert("Đã xóa sạch lịch sử.");
        loadSavedData();
      }
    } catch (err) {
      alert("Lỗi khi xóa lịch sử: " + err.message);
    }
  }

  async function handleViewSavedItems() {
    try {
      const res = await fetch(API_RESULTS, { headers: { Accept: "application/json" } });
      if (!res.ok) throw new Error("Lỗi mạng khi tải dữ liệu.");
      const data = await res.json();
      const items = Array.isArray(data.items) ? data.items : [];

      if (items.length === 0) {
        alert("Chưa có dữ liệu nào trong cơ sở dữ liệu.");
        return;
      }

      showAllResults({ products: items, links: [], images: [], articles: [], headings: [], files: [], html: "" }, 0);
      setStatus("info", "Đang hiển thị " + items.length + " mục được tổng hợp từ cơ sở dữ liệu đã lưu.");

      const triggerEl = document.querySelector('#crawler-tab');
      if (triggerEl && window.bootstrap) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
      }
    } catch (e) {
      alert("Không thể tải dữ liệu đã lưu: " + e.message);
    }
  }

  // ===== Xuất dữ liệu (Export Specific / All) =====
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

  function csvCell(value) {
    let s = value == null ? "" : String(value);
    if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; // Chống CSV / Formula injection
    return '"' + s.replace(/"/g, '""') + '"';
  }

  function exportSpecificJson(type) {
    let data = [];
    let filename = "crawl_" + type + "_";

    if (type === "articles") data = getFilteredArticles();
    else if (type === "headings") data = getFilteredHeadings();
    else if (type === "files") data = getFilteredFiles();
    else if (type === "products") data = getFilteredProducts();
    else if (type === "links") data = getFilteredLinks();
    else if (type === "images") data = getFilteredImages();
    else {
      data = {
        articles: state.articles,
        headings: state.headings,
        files: state.files,
        products: state.items,
        links: state.links,
        images: state.images
      };
      filename = "crawl_full_";
    }

    downloadFile(filename + getTimestamp() + ".json", "application/json;charset=utf-8", JSON.stringify(data, null, 2));
  }

  function exportSpecificCsv(type) {
    let cols = [];
    let list = [];
    let filename = "crawl_" + type + "_";

    if (type === "articles") {
      cols = ["title", "author", "date", "url", "excerpt", "image_url"];
      list = getFilteredArticles();
    } else if (type === "headings") {
      cols = ["tag", "level", "text", "id"];
      list = getFilteredHeadings();
    } else if (type === "files") {
      cols = ["name", "extension", "url", "anchor_text", "is_internal"];
      list = getFilteredFiles();
    } else if (type === "products") {
      cols = ["title", "url", "price", "currency", "rating", "availability", "image_url"];
      list = getFilteredProducts();
    } else if (type === "links") {
      cols = ["text", "url", "type", "is_internal"];
      list = getFilteredLinks();
    } else if (type === "images") {
      cols = ["alt", "src", "extension", "is_internal"];
      list = getFilteredImages();
    } else {
      // Default to current active tab
      return exportSpecificCsv(state.currentView === "raw" ? "articles" : state.currentView);
    }

    if (!list.length) {
      alert("Không có dữ liệu '" + type + "' để xuất CSV.");
      return;
    }

    const lines = [cols.join(",")].concat(
      list.map((it) => cols.map((c) => csvCell(it[c])).join(","))
    );

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

  // Presets XPath
  document.querySelectorAll(".xpath-preset").forEach((badge) => {
    badge.addEventListener("click", () => {
      customXpathInput.value = badge.dataset.xpath;
    });
  });

  // Filter Articles
  filterArtInput.addEventListener("input", () => {
    state.filterArtText = filterArtInput.value;
    renderArticlesTable();
  });
  btnResetArtFilter.addEventListener("click", () => {
    state.filterArtText = "";
    filterArtInput.value = "";
    renderArticlesTable();
  });

  // Filter Headings
  filterHeadingInput.addEventListener("input", () => {
    state.filterHeadText = filterHeadingInput.value;
    renderHeadingsTable();
  });
  filterHeadingLevel.addEventListener("change", () => {
    state.filterHeadLevel = filterHeadingLevel.value;
    renderHeadingsTable();
  });
  btnResetHeadFilter.addEventListener("click", () => {
    state.filterHeadText = "";
    state.filterHeadLevel = "";
    filterHeadingInput.value = "";
    filterHeadingLevel.value = "";
    renderHeadingsTable();
  });

  // Filter Files
  filterFileInput.addEventListener("input", () => {
    state.filterFileText = filterFileInput.value;
    renderFilesTable();
  });
  filterFileExt.addEventListener("change", () => {
    state.filterFileExt = filterFileExt.value;
    renderFilesTable();
  });
  btnResetFileFilter.addEventListener("click", () => {
    state.filterFileText = "";
    state.filterFileExt = "";
    filterFileInput.value = "";
    filterFileExt.value = "";
    renderFilesTable();
  });

  // Filter Products
  filterInput.addEventListener("input", () => {
    state.filterText = filterInput.value;
    renderProductsTable();
  });
  filterRating.addEventListener("change", () => {
    state.filterRating = filterRating.value;
    renderProductsTable();
  });
  filterStock.addEventListener("change", () => {
    state.filterStock = filterStock.value;
    renderProductsTable();
  });
  btnResetFilter.addEventListener("click", () => {
    state.filterText = "";
    state.filterRating = "";
    state.filterStock = "";
    filterInput.value = "";
    filterRating.value = "";
    filterStock.value = "";
    renderProductsTable();
  });

  // Filter Links
  filterLinkInput.addEventListener("input", () => {
    state.filterLinkText = filterLinkInput.value;
    renderLinksTable();
  });
  filterLinkType.addEventListener("change", () => {
    state.filterLinkType = filterLinkType.value;
    renderLinksTable();
  });
  btnResetLinkFilter.addEventListener("click", () => {
    state.filterLinkText = "";
    state.filterLinkType = "";
    filterLinkInput.value = "";
    filterLinkType.value = "";
    renderLinksTable();
  });

  // Filter Images
  filterImgInput.addEventListener("input", () => {
    state.filterImgText = filterImgInput.value;
    renderImagesGrid();
  });
  filterImgExt.addEventListener("change", () => {
    state.filterImgExt = filterImgExt.value;
    renderImagesGrid();
  });
  btnResetImgFilter.addEventListener("click", () => {
    state.filterImgText = "";
    state.filterImgExt = "";
    filterImgInput.value = "";
    filterImgExt.value = "";
    renderImagesGrid();
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
  $("view-articles-tab").addEventListener("click", () => switchView("articles"));
  $("view-headings-tab").addEventListener("click", () => switchView("headings"));
  $("view-files-tab").addEventListener("click", () => switchView("files"));
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

  // Xuất file từ menu dropdown và nút chính
  $("export-json").addEventListener("click", () => exportSpecificJson("all"));
  $("export-csv").addEventListener("click", () => exportSpecificCsv(state.currentView === "raw" ? "all" : state.currentView));
  $("btn-copy-html").addEventListener("click", copyRawHtml);

  document.querySelectorAll(".export-action").forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault();
      const type = item.dataset.type;
      const format = item.dataset.format;
      if (format === "json") exportSpecificJson(type);
      else exportSpecificCsv(type);
    });
  });

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
  btnClearSaved.addEventListener("click", handleClearAllSaved);

  // Khởi động
  loadSavedData();
})();
