<?php
header('Content-Type: application/json; charset=utf-8');
$file=__DIR__.'/../data/crawl_data.json';
if (!is_file($file)) { echo json_encode(['success'=>true,'items'=>[]], JSON_UNESCAPED_UNICODE); exit; }
$data=json_decode(file_get_contents($file),true);
if (!is_array($data)) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Không đọc được dữ liệu đã lưu.'], JSON_UNESCAPED_UNICODE); exit; }
$items=[];
foreach ($data as $record) {
    $base=$record['url'] ?? '';
    if (!empty($record['products']) && is_array($record['products'])) {
        foreach ($record['products'] as $item) {
            $item['url']=resolveSavedUrl($item['url'] ?? '',$base);
            $item['image_url']=resolveSavedUrl($item['image_url'] ?? '',$base);
            if (!empty($item['url'])) $items[]=$item;
        }
    } elseif (!empty($record['links']) && is_array($record['links'])) {
        foreach ($record['links'] as $link) {
            $href=resolveSavedUrl($link['url'] ?? '',$base);
            if ($href) $items[]=['title'=>$link['text'] ?? '(không có tiêu đề)','url'=>$href,'image_url'=>'','price'=>null,'currency'=>'GBP','rating'=>null,'availability'=>''];
        }
    }
}
$unique=[]; $seen=[];
foreach ($items as $item) { $url=$item['url'] ?? ''; if ($url && !isset($seen[$url])) {$seen[$url]=true;$unique[]=$item;} }

$recordsSummary = [];
foreach ($data as $i => $rec) {
    $recordsSummary[] = [
        'id' => $i + 1,
        'url' => $rec['url'] ?? '',
        'saved_at' => $rec['saved_at'] ?? '',
        'title' => $rec['title'] ?? '',
        'total_links' => isset($rec['total_links']) ? (int)$rec['total_links'] : (is_array($rec['links'] ?? null) ? count($rec['links']) : 0),
        'total_images' => isset($rec['total_images']) ? (int)$rec['total_images'] : (is_array($rec['images'] ?? null) ? count($rec['images']) : 0),
        'total_products' => is_array($rec['products'] ?? null) ? count($rec['products']) : 0,
    ];
}
echo json_encode([
    'success' => true,
    'total_records' => count($data),
    'records' => array_reverse($recordsSummary),
    'items' => $unique
], JSON_UNESCAPED_UNICODE);
function resolveSavedUrl($value,$base) { if (!$value) return ''; if (preg_match('~^https?://~i',$value)) return $value; if (!$base) return $value; $p=parse_url($base); if (!$p || empty($p['scheme']) || empty($p['host'])) return $value; $origin=$p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:''); if (strpos($value,'//')===0) return $p['scheme'].':'.$value; if (strpos($value,'/')===0) return $origin.$value; $path=$p['path'] ?? '/'; $dir=preg_replace('~/[^/]*$~','/',$path); $full=$origin.$dir.$value; $parts=[]; foreach(explode('/',$full) as $seg) {if($seg==='.'||$seg==='') {if(!$parts) $parts[]=''; continue;} if($seg==='..') array_pop($parts); else $parts[]=$seg;} return implode('/',$parts); }
