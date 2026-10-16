
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Crawler</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            background: #f5f7fa;
        }

        input {
            width: 70%;
            padding: 10px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 16px;
            cursor: pointer;
        }

        .section {
            background: white;
            padding: 18px;
            margin-top: 20px;
            border-radius: 8px;
            overflow-wrap: anywhere;
        }

        .item {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }

        .item a {
            overflow-wrap: anywhere;
        }

        .item img {
            display: block;
            max-width: 140px;
            max-height: 140px;
            object-fit: contain;
            margin-top: 8px;
        }

        #status {
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <h2>Web Crawler - Thu thập dữ liệu website</h2>

    <input
        type="url"
        id="url"
        placeholder="https://books.toscrape.com/"
        value="https://books.toscrape.com/"
    >

    <button id="crawlButton" onclick="crawlWebsite()">
        Bắt đầu Crawl
    </button>

    <p id="status"></p>

    <div class="section">
        <h3>1. Thông tin website</h3>
        <p id="pageTitle">Chưa có dữ liệu</p>
        <p id="totalLinks">Số lượng liên kết: 0</p>
        <p id="totalImages">Số lượng hình ảnh: 0</p>
        <p id="saveStatus">Trạng thái lưu: Chưa lưu</p>
    </div>

    <div class="section">
        <h3>2. Danh sách liên kết</h3>
        <div id="links"></div>
    </div>

    <div class="section">
        <h3>3. Danh sách hình ảnh</h3>
        <div id="images"></div>
    </div>

    <script>
        async function crawlWebsite() {
            const urlInput = document.getElementById('url');
            const status = document.getElementById('status');
            const button = document.getElementById('crawlButton');

            const pageTitle = document.getElementById('pageTitle');
            const totalLinks = document.getElementById('totalLinks');
            const totalImages = document.getElementById('totalImages');
            const linksContainer = document.getElementById('links');
            const imagesContainer = document.getElementById('images');

            const url = urlInput.value.trim();

            if (!url) {
                status.textContent = 'Vui lòng nhập địa chỉ website.';
                return;
            }

            // Xóa kết quả cũ
            pageTitle.textContent = 'Đang xử lý...';
            totalLinks.textContent = 'Số lượng liên kết: 0';
            totalImages.textContent = 'Số lượng hình ảnh: 0';
            const saveStatus = document.getElementById('saveStatus');
            if (saveStatus) {
                saveStatus.textContent = 'Trạng thái lưu: Đang xử lý...';
            }
            linksContainer.replaceChildren();
            imagesContainer.replaceChildren();

            button.disabled = true;
            status.textContent = 'Bước 1/3: Đang lấy HTML từ website...';

            try {
                // BƯỚC 1: Gọi crawl.php để lấy HTML
                const crawlResponse = await fetch('backend/crawl.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({ url: url })
                });

                const crawlData = await crawlResponse.json();

                if (!crawlResponse.ok || !crawlData.success) {
                    throw new Error(
                        crawlData.message || 'Không thể crawl website.'
                    );
                }

                if (!crawlData.html) {
                    throw new Error('Website không trả về nội dung HTML.');
                }

                // BƯỚC 2: Gửi HTML cho parse_html.php phân tích
                status.textContent =
                    'Bước 2/3: Đang phân tích HTML, lấy liên kết và hình ảnh...';

                const parseResponse = await fetch('backend/parse_html.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        html: crawlData.html
                    })
                });

                const parseData = await parseResponse.json();

                if (!parseResponse.ok || !parseData.success) {
                    throw new Error(
                        parseData.message || 'Không thể phân tích HTML.'
                    );
                }

                // BƯỚC 3: Gửi dữ liệu cho save_data.php để lưu vào crawl_data.json
                status.textContent =
                    'Bước 3/3: Đang lưu dữ liệu vào data/crawl_data.json...';

                const saveResponse = await fetch('backend/save_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        url: url,
                        html: crawlData.html,
                        title: parseData.title || '',
                        total_links: parseData.total_links || 0,
                        total_images: parseData.total_images || 0,
                        links: parseData.links || [],
                        images: parseData.images || []
                    })
                });

                const saveData = await saveResponse.json();

                if (!saveResponse.ok || !saveData.success) {
                    throw new Error(
                        saveData.message || 'Không thể lưu dữ liệu vào file.'
                    );
                }

                if (saveStatus) {
                    saveStatus.innerHTML =
                        'Trạng thái lưu: <span style="color: #27ae60; font-weight: bold;">Đã lưu thành công</span> (' +
                        (saveData.saved_at || '') +
                        ') - <a href="data/crawl_data.json" target="_blank">[Xem file crawl_data.json]</a>';
                }

                // Hiển thị tiêu đề và số lượng
                pageTitle.textContent =
                    'Tiêu đề: ' + (parseData.title || 'Không có tiêu đề');

                totalLinks.textContent =
                    'Số lượng liên kết: ' + parseData.total_links;

                totalImages.textContent =
                    'Số lượng hình ảnh: ' + parseData.total_images;

                // Hiển thị tối đa 50 liên kết
                const links = parseData.links || [];

                if (links.length === 0) {
                    linksContainer.textContent = 'Không tìm thấy liên kết.';
                } else {
                    links.slice(0, 50).forEach(function(item) {
                        const div = document.createElement('div');
                        div.className = 'item';

                        const text = document.createElement('p');
                        text.textContent = item.text || '(Liên kết không có chữ)';
                        div.appendChild(text);

                        try {
                            const linkUrl = new URL(item.url, url);

                            if (
                                linkUrl.protocol === 'http:' ||
                                linkUrl.protocol === 'https:'
                            ) {
                                const a = document.createElement('a');
                                a.href = linkUrl.href;
                                a.textContent = linkUrl.href;
                                a.target = '_blank';
                                a.rel = 'noopener noreferrer';
                                div.appendChild(a);
                            } else {
                                const note = document.createElement('p');
                                note.textContent = 'Địa chỉ liên kết không được hỗ trợ.';
                                div.appendChild(note);
                            }
                        } catch {
                            const note = document.createElement('p');
                            note.textContent = 'Địa chỉ liên kết không hợp lệ.';
                            div.appendChild(note);
                        }

                        linksContainer.appendChild(div);
                    });

                    if (links.length > 50) {
                        const note = document.createElement('p');
                        note.textContent =
                            'Chỉ hiển thị 50/' + links.length + ' liên kết đầu tiên.';
                        linksContainer.appendChild(note);
                    }
                }

                // Hiển thị tối đa 30 hình ảnh
                const images = parseData.images || [];

                if (images.length === 0) {
                    imagesContainer.textContent = 'Không tìm thấy hình ảnh.';
                } else {
                    images.slice(0, 30).forEach(function(item) {
                        const div = document.createElement('div');
                        div.className = 'item';

                        const alt = document.createElement('p');
                        alt.textContent = item.alt || 'Hình ảnh không có mô tả';
                        div.appendChild(alt);

                        try {
                            const imageUrl = new URL(item.src, url);

                            if (
                                imageUrl.protocol === 'http:' ||
                                imageUrl.protocol === 'https:'
                            ) {
                                const img = document.createElement('img');
                                img.src = imageUrl.href;
                                img.alt = item.alt || '';
                                img.loading = 'lazy';
                                img.referrerPolicy = 'no-referrer';

                                div.appendChild(img);

                                const source = document.createElement('p');
                                source.textContent = 'Nguồn: ' + imageUrl.href;
                                div.appendChild(source);
                            } else {
                                const note = document.createElement('p');
                                note.textContent = 'Địa chỉ hình ảnh không được hỗ trợ.';
                                div.appendChild(note);
                            }
                        } catch {
                            const note = document.createElement('p');
                            note.textContent = 'Địa chỉ hình ảnh không hợp lệ.';
                            div.appendChild(note);
                        }

                        imagesContainer.appendChild(div);
                    });

                    if (images.length > 30) {
                        const note = document.createElement('p');
                        note.textContent =
                            'Chỉ hiển thị 30/' + images.length + ' hình ảnh đầu tiên.';
                        imagesContainer.appendChild(note);
                    }
                }

                status.textContent = 'Crawl, phân tích và lưu dữ liệu thành công!';

            } catch (error) {
                status.textContent = 'Có lỗi xảy ra: ' + error.message;
                pageTitle.textContent = 'Chưa lấy được dữ liệu.';
                if (saveStatus) {
                    saveStatus.textContent = 'Trạng thái lưu: Thất bại (' + error.message + ')';
                }
            } finally {
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
