<?php
header('Content-Type: application/json; charset=utf-8');
if (!class_exists('DOMDocument')) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'PHP chưa bật extension DOM. Hãy bật extension=dom trong php.ini rồi khởi động lại máy chủ.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Vui lòng gửi yêu cầu POST.'], JSON_UNESCAPED_UNICODE); exit;
}
$html = $_POST['html'] ?? '';
$baseUrl = trim($_POST['url'] ?? '');
if (trim($html) === '') {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Chưa có nội dung HTML để phân tích.'], JSON_UNESCAPED_UNICODE); exit;
}
libxml_use_internal_errors(true);
$dom = new DOMDocument('1.0','UTF-8');
$loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
libxml_clear_errors();
if (!$loaded) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Không thể phân tích HTML.'], JSON_UNESCAPED_UNICODE); exit; }
$xpath = new DOMXPath($dom);
$titleNode = $xpath->query('//title')->item(0);
$title = $titleNode ? trim($titleNode->textContent) : 'Không có tiêu đề';
$links=[];
foreach ($xpath->query('//a[@href]') as $node) {
    if (!$node instanceof DOMElement) continue;
    $href=trim($node->getAttribute('href')); if ($href==='') continue;
    $links[]=['text'=>trim(preg_replace('/\\s+/u',' ',$node->textContent)),'url'=>$href];
}
$images=[];
foreach ($xpath->query('//img[@src]') as $node) {
    if (!$node instanceof DOMElement) continue;
    $src=trim($node->getAttribute('src')); if ($src==='') continue;
    $images[]=['alt'=>$node->getAttribute('alt'),'src'=>$src];
}
$products=[];
foreach ($xpath->query("//article[contains(concat(' ', normalize-space(@class), ' '), ' product_pod ')]") as $card) {
    $nameNode=$xpath->query('.//h3/a', $card)->item(0);
    $priceNode=$xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' price_color ')]", $card)->item(0);
    $availabilityNode=$xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' availability ')]", $card)->item(0);
    $imageNode=$xpath->query('.//img[@src]', $card)->item(0);
    $ratingNode=$xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' star-rating ')]", $card)->item(0);
    $rating=null;
    if ($ratingNode) { $classes=$ratingNode->getAttribute('class'); $map=['One'=>1,'Two'=>2,'Three'=>3,'Four'=>4,'Five'=>5]; foreach($map as $word=>$num) if(preg_match('/\\b'.preg_quote($word,'/').'\\b/',$classes)) {$rating=$num;break;} }
    $priceText=$priceNode ? trim($priceNode->textContent) : '';
    $price=null; if (preg_match('/([0-9]+(?:\\.[0-9]{1,2})?)/',$priceText,$m)) $price=(float)$m[1];
    $products[]=[
        'title'=>$nameNode ? trim($nameNode->getAttribute('title') ?: $nameNode->textContent) : '(không có tiêu đề)',
        'url'=>$nameNode ? trim($nameNode->getAttribute('href')) : '',
        'image_url'=>$imageNode ? trim($imageNode->getAttribute('src')) : '',
        'price'=>$price, 'currency'=>'GBP', 'rating'=>$rating,
        'availability'=>$availabilityNode ? trim(preg_replace('/\\s+/u',' ',$availabilityNode->textContent)) : ''
    ];
}
echo json_encode(['success'=>true,'title'=>$title,'total_links'=>count($links),'total_images'=>count($images),'links'=>$links,'images'=>$images,'products'=>$products], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
