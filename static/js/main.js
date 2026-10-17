/* Web Data Crawler - frontend (Thành viên 2)
 * Gửi yêu cầu tới backend PHP bằng fetch() và hiển thị kết quả.
 * Hợp đồng API: xem docs/source_research.md, mục 7.
 */
(function () {
  "use strict";

  // ===== Cấu hình =====
  const API_CRAWL = "backend/crawl.php";
  const API_RESULTS = "backend/results.php";
  const BASE_TIMEOUT_MS = 20000;   // thời gian chờ cơ bản
  const PER_PAGE_TIMEOUT_MS = 4000; // cộng thêm cho mỗi trang cần duyệt

  // ===== Tham chiếu DOM =====
  const $ = (id) => document.getElementById(id);
  const form = $("crawl-form");
  const urlInput = $("url-input");
  const maxPagesInput = $("max-pages");
  const crawlBtn = $("crawl-btn");
  const btnSpinner = $("btn-spinner");
  const btnIcon = $("btn-icon");
  const btnText = $("btn-text");
  const statusArea = $("status-area");
  const summary = $("summary");
  const resultsCard = $("results-card");
  const resultsBody = $("results-body");
  const countBadge = $("count-badge");
  const filterInput = $("filter-input");
  const emptyFilter = $("empty-filter");
  const warningsBox = $("warnings");
  const savedCard = $("saved-card");
  const savedCount = $("saved-count");

  // ===== Trạng thái =====
  const state = {
    items: [],
    sortKey: null,
    sortDir: 1,
    filter: "",
    busy: false,
    meta: {},
  };

  // ===== Tiện ích =====
  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  // Chỉ chấp nhận http/https cho href và src (chặn javascript:, data:...)
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
    let out = '<span class="stars" title="' + full + ' / 5">';
    for (let i = 1; i <= 5; i++) {
      out += i <= full ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star-fill off"></i>';
    }
    return out + "</span>";
  }

  function stockBadge(text) {
    if (!text) return '<span class="text-muted">—</span>';
    const ok = /in stock/i.test(text);
    return '<span class="badge badge-stock ' + (ok ? "text-bg-success" : "text-bg-secondary") + '">' + escapeHtml(text) + "</span>";
  }

  // ===== Trạng thái giao diện =====
  function setStatus(type, message, code) {
    // type: info | success | danger | warning
    const icons = { info: "bi-hourglass-split", success: "bi-check-circle-fill", danger: "bi-x-octagon-fill", warning: "bi-exclamation-triangle-fill" };
    const codeHtml = code ? '<div class="alert-code">Mã lỗi: ' + escapeHtml(code) + "</div>" : "";
    statusArea.innerHTML =
      '<div class="alert alert-' + type + ' mb-0" role="alert"><div class="alert-body">' +
      '<i class="bi ' + (icons[type] || icons.info) + ' fs-4"></i>' +
      "<div><div>" + escapeHtml(message) + "</div>" + codeHtml + "</div></div></div>";
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
    btnText.textContent = busy ? "Đang thu thập..." : "Thu thập";
  }

  // ===== Hiển thị kết quả =====
  function getVisibleItems() {
    const q = state.filter.trim().toLowerCase();
    let list = state.items.filter((it) => !q || String(it.title || "").toLowerCase().includes(q));
    if (state.sortKey) {
      const key = state.sortKey;
      const dir = state.sortDir;
      list = list.slice().sort((a, b) => {
        const va = a[key];
        const vb = b[key];
        const na = va == null || va === "";
        const nb = vb == null || vb === "";
        if (na && nb) return 0;
        if (na) return 1;   // giá trị thiếu luôn xuống cuối
        if (nb) return -1;
        if (typeof va === "number" && typeof vb === "number") return (va - vb) * dir;
        return String(va).localeCompare(String(vb), "vi", { sensitivity: "base" }) * dir;
      });
    }
    return list;
  }

  function renderTable() {
    const list = getVisibleItems();
    countBadge.textContent = list.length === state.items.length ? String(list.length) : list.length + " / " + state.items.length;

    resultsBody.innerHTML = list.map((it, idx) => {
      const href = safeHttpUrl(it.url);
      const img = safeHttpUrl(it.image_url);
      const title = escapeHtml(it.title || "(không có tiêu đề)");
      const titleCell = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer">' + title + "</a>"
        : title;
      const urlCell = href
        ? '<a href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer" class="small" title="' + escapeHtml(href) + '">' + escapeHtml(href) + "</a>"
        : '<span class="text-muted">—</span>';
      const imgCell = img
        ? '<img class="thumb" loading="lazy" src="' + escapeHtml(img) + '" alt="" referrerpolicy="no-referrer">'
        : '<div class="thumb"></div>';
      return (
        "<tr>" +
        '<td class="text-muted">' + (idx + 1) + "</td>" +
        "<td>" + imgCell + "</td>" +
        '<td class="title-cell">' + titleCell + "</td>" +
        '<td class="text-end fw-semibold">' + escapeHtml(formatPrice(it)) + "</td>" +
        "<td>" + starsHtml(it.rating) + "</td>" +
        "<td>" + stockBadge(it.availability) + "</td>" +
        '<td class="url-cell">' + urlCell + "</td>" +
        "</tr>"
      );
    }).join("");

    emptyFilter.classList.toggle("d-none", list.length > 0 || state.items.length === 0);

    document.querySelectorAll("th.sortable").forEach((th) => {
      const active = th.dataset.key === state.sortKey;
      th.classList.toggle("sort-active", active);
      const icon = th.querySelector(".sort-icon");
      if (icon) {
        icon.className = "bi sort-icon " + (active ? (state.sortDir === 1 ? "bi-sort-up" : "bi-sort-down") : "bi-arrow-down-up");
      }
    });
  }

  function renderSummary(meta, elapsedSec) {
    $("stat-items").textContent = state.items.length;
    $("stat-pages").textContent = meta.pages_crawled != null ? meta.pages_crawled : "—";
    $("stat-dups").textContent = meta.duplicates_removed != null ? meta.duplicates_removed : 0;
    $("stat-time").textContent = elapsedSec != null ? elapsedSec.toFixed(1) : "—";
    summary.classList.remove("d-none");
  }

  function renderWarnings(list) {
    if (!Array.isArray(list) || list.length === 0) {
      warningsBox.innerHTML = "";
      return;
    }
    warningsBox.innerHTML =
      '<div class="alert alert-warning small mb-0"><strong>Cảnh báo (' + list.length + "):</strong><ul class=\"mb-0\">" +
      list.map((w) => "<li>" + escapeHtml(w) + "</li>").join("") + "</ul></div>";
  }

  function showResults(items, meta, elapsedSec) {
    state.items = Array.isArray(items) ? items : [];
    state.meta = meta || {};
    state.filter = "";
    filterInput.value = "";
    renderSummary(state.meta, elapsedSec);
    renderWarnings(state.meta.warnings);
    resultsCard.classList.remove("d-none");
    renderTable();
  }

  // ===== Gọi API =====
  async function postCrawl(url, maxPages) {
    async function readJson(response) {
      const raw = await response.text();
      let data;
      try { data = JSON.parse(raw); } catch (e) { throw new Error("Backend PHP không trả về JSON hợp lệ."); }
      if (!response.ok || data.success === false) {
        const error = new Error(data.message || "Yêu cầu thất bại (HTTP " + response.status + ").");
        error.code = data.code || "HTTP_" + response.status;
        throw error;
      }
      return data;
    }

    const crawlResponse = await fetch(API_CRAWL, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
      body: new URLSearchParams({ url: url })
    });
    const crawlData = await readJson(crawlResponse);
    if (!crawlData.html) throw new Error("Website không trả về nội dung HTML.");

    const parseResponse = await fetch("backend/parse_html.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", Accept: "application/json" },
      body: new URLSearchParams({ html: crawlData.html, url: url })
    });
    const parsed = await readJson(parseResponse);

    const saveResponse = await fetch("backend/save_data.php", {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({
        url: url, html: crawlData.html, title: parsed.title || "",
        total_links: parsed.total_links || 0, total_images: parsed.total_images || 0,
        links: parsed.links || [], images: parsed.images || [], products: parsed.products || []
      })
    });
    const saved = await readJson(saveResponse);

    let items = Array.isArray(parsed.products) ? parsed.products : [];
    if (!items.length) {
      items = (parsed.links || []).map((link) => ({
        title: link.text || "(không có tiêu đề)",
        url: resolveUrl(link.url, url),
        image_url: "", price: null, currency: "GBP", rating: null, availability: ""
      }));
    }
    const seen = new Set();
    const before = items.length;
    items = items.filter((item) => {
      item.url = resolveUrl(item.url || "", url);
      if (!item.url || seen.has(item.url)) return false;
      seen.add(item.url); return true;
    });
    return {
      status: "success", success: true, message: saved.message || "Thu thập và lưu dữ liệu thành công.",
      title: parsed.title || "", items: items,
      pages_crawled: 1, duplicates_removed: before - items.length,
      warnings: [], saved_at: saved.saved_at
    };
  }

  function resolveUrl(value, base) {
    try { return new URL(String(value || ""), base).href; } catch (e) { return ""; }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (state.busy) return;

    const url = urlInput.value.trim();
    const maxPages = parseInt(maxPagesInput.value, 10);
    let valid = true;

    urlInput.classList.toggle("is-invalid", !isValidUrl(url));
    if (!isValidUrl(url)) valid = false;
    const pagesOk = Number.isInteger(maxPages) && maxPages === 1;
    maxPagesInput.classList.toggle("is-invalid", !pagesOk);
    if (!pagesOk) valid = false;
    if (!valid) {
      setStatus("warning", "Vui lòng kiểm tra URL hợp lệ và chọn số trang là 1.");
      return;
    }

    setBusy(true);
    setStatus("info", "Đang thu thập dữ liệu từ " + url + " ...");
    const started = performance.now();
    try {
      const data = await postCrawl(url, maxPages);
      const elapsed = (performance.now() - started) / 1000;
      const items = Array.isArray(data.items) ? data.items : [];
      if (items.length === 0) {
        summary.classList.add("d-none");
        resultsCard.classList.add("d-none");
        setStatus("warning", data.message || "Tải được trang nhưng không tìm thấy dữ liệu phù hợp.", data.code || "NO_DATA");
      } else {
        showResults(items, data, elapsed);
        setStatus("success", "Thu thập thành công " + items.length + " mục trong " + elapsed.toFixed(1) + " giây.");
        refreshSavedInfo();
      }
    } catch (e) {
      setStatus("danger", e.message || "Đã xảy ra lỗi không xác định.", e.code);
    } finally {
      setBusy(false);
    }
  }

  // ===== Dữ liệu đã lưu (tuần 2; nếu backend chưa có endpoint thì bỏ qua êm) =====
  async function fetchSaved() {
    try {
      const res = await fetch(API_RESULTS, { headers: { Accept: "application/json" } });
      if (!res.ok) return null;
      const data = await res.json();
      return Array.isArray(data.items) ? data.items : null;
    } catch (e) {
      return null;
    }
  }

  async function refreshSavedInfo() {
    const saved = await fetchSaved();
    if (saved === null) {
      savedCard.classList.add("d-none");
      return;
    }
    savedCount.textContent = saved.length;
    savedCard.classList.remove("d-none");
  }

  async function handleLoadSaved() {
    const saved = await fetchSaved();
    if (saved === null) {
      setStatus("danger", "Không tải được dữ liệu đã lưu.");
      return;
    }
    if (saved.length === 0) {
      setStatus("warning", "Chưa có dữ liệu nào được lưu.");
      return;
    }
    showResults(saved, { pages_crawled: null, duplicates_removed: null }, null);
    setStatus("info", "Đang hiển thị " + saved.length + " mục đã lưu.");
  }

  // ===== Xuất dữ liệu =====
  function download(filename, mime, content) {
    const blob = new Blob([content], { type: mime });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  function timestamp() {
    const d = new Date();
    const p = (n) => String(n).padStart(2, "0");
    return d.getFullYear() + p(d.getMonth() + 1) + p(d.getDate()) + "_" + p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
  }

  function exportJson() {
    const list = getVisibleItems();
    if (!list.length) return;
    download("crawl_" + timestamp() + ".json", "application/json;charset=utf-8", JSON.stringify(list, null, 2));
  }

  function csvCell(value) {
    let s = value == null ? "" : String(value);
    if (/^[=+\-@\t\r]/.test(s)) s = "'" + s; // chống CSV/formula injection khi mở bằng Excel
    return '"' + s.replace(/"/g, '""') + '"';
  }

  function exportCsv() {
    const list = getVisibleItems();
    if (!list.length) return;
    const cols = ["title", "url", "price", "currency", "rating", "availability", "image_url"];
    const lines = [cols.join(",")].concat(list.map((it) => cols.map((c) => csvCell(it[c])).join(",")));
    download("crawl_" + timestamp() + ".csv", "text/csv;charset=utf-8", "﻿" + lines.join("\r\n")); // BOM để Excel đọc đúng UTF-8
  }

  // ===== Sự kiện =====
  form.addEventListener("submit", handleSubmit);
  urlInput.addEventListener("input", () => urlInput.classList.remove("is-invalid"));
  maxPagesInput.addEventListener("input", () => maxPagesInput.classList.remove("is-invalid"));
  filterInput.addEventListener("input", () => {
    state.filter = filterInput.value;
    renderTable();
  });
  document.querySelectorAll(".example-url").forEach((btn) => {
    btn.addEventListener("click", () => {
      urlInput.value = btn.dataset.url;
      urlInput.classList.remove("is-invalid");
      urlInput.focus();
    });
  });
  document.querySelectorAll("th.sortable").forEach((th) => {
    th.addEventListener("click", () => {
      const key = th.dataset.key;
      if (state.sortKey === key) state.sortDir = -state.sortDir;
      else { state.sortKey = key; state.sortDir = 1; }
      renderTable();
    });
  });
  $("export-json").addEventListener("click", exportJson);
  $("export-csv").addEventListener("click", exportCsv);
  $("load-saved").addEventListener("click", handleLoadSaved);

  refreshSavedInfo();
})();
