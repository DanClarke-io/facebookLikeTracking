<?php

$apis[] = array('url'=>'URL_TO_TRACK','name'=>'unique-name');
$apis[] = array('url'=>'URL_TO_TRACK','name'=>'unique-name1');
$apis[] = array('url'=>'URL_TO_TRACK','name'=>'unique-name2');
$apis[] = array('url'=>'URL_TO_TRACK','name'=>'unique-name3');

?>
<html>
<head>
  <title>Like tracking</title>
  <style type="text/css">
  body { font-family:sans-serif; }
  .line { position:relative; width:80%; border-bottom:1px solid #CCC; padding:0 0 2px 0; margin:0 0 8px 0;  float:left; }
  .line.strong { font-weight:bold; }
  .name { top:0; left:0; z-index:2; position:absolute; display:inline-block; min-width:150px; padding:5px; background:rgba(255,255,255,0.8); border-right:1px solid #888; border-bottom:1px solid #888; border-bottom-right-radius: 5px; }
  .amount { z-index:2; position:absolute; display:inline-block; min-width:50px; }
  .amount.higher { color:red; }
  .amount.lower { color:green; }
  .clear { clear:both; }
  .graph { float:left; background:#CCC; width: 100%; height:50px; position:relative; }
  .graph-line { font-weight:normal; font-size:0.5em; text-align:center; box-sizing:border-box; padding-top:2px; float:left; bottom:0; position:absolute; background:#50B0F0; border-top:1px solid red; }
  .graph-line.data-more { min-width:1%; background:#50F09B; }
  .graph-line.data-less { min-width:1%; background:#DB7474; }
  .graph-line span { opacity:0;
    margin-top:-25px;
    position:relative;
    z-index:10;
    background:rgba(0,0,0,0.5);
    color:#FFF;
    padding:2px 2px 1px 2px;
    line-height:1em;
    float:left;
    border-radius: 3px;
 }
  .graph-line:hover { background:#50B0CC; }
  .graph-line:hover span { opacity:1; }
  </style>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function() {
    var curAmount = parseInt($('.line.strong .amount').text());
    $('.line').each(function(x,e) {
      if(!$(this).hasClass('strong')) {
        var val = $('.amount',this).text().replace(',','');
        if(parseInt(val)>curAmount) { $('.amount',this).addClass('higher'); }
        else { $('.amount',this).addClass('lower'); }
      }
    });
  })
  </script>
</head>
<body>
  <?php


  function clearName($url,$cache=TRUE) {
    $name = substr($url['name'],strrpos($url['name'],'/',-2)+1,-1);
    $file = $name.'.txt';
    if($cache) { $file = 'cache/'.$file; }
    return $file;
  }

  function get_data($url,$name) {
    $file = clearName($name);
    $run = true;
    if(file_exists($file)) {
      if(filemtime($file)>strtotime('10 minutes ago')) {
        $run = false;
        return file_get_contents($file);
      }
    }
    if($run==true) {
      $ch = curl_init();
      $timeout = 5;
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      $data = curl_exec($ch);
      curl_close($ch);

      file_put_contents($file.'-'.date('m-d-Y_hia').'.txt',$data);
      file_put_contents($file,$data);
      return $data;
    }
  }

  echo '<p>Updated every 10 minutes.</p>';
  foreach($apis as $data) {
    $fileName = clearName($data,true);
    $json = json_decode(get_data('http://graph.facebook.com/?fields=og_object{likes.summary(true).limit(0)},share&id='.$data['url'],$data['url']));
    $name = ucwords(str_replace('-',' ',str_replace('/','',substr($data['url'],strrpos($data['url'],'/',-2)))));
    echo '<div class="line">';
    if(isset($json->error)) {
      echo '<span class="name">'.$name.'</span><code> Error: '.$json->error->message.'</code>';
    }
    else {
      if(isset($json->share->share_count)) {
        echo '<span class="name">'.$name.': '.number_format($json->share->share_count).($json->share->share_count<=0?'?':'').'</span>';
      }
      $d = dir('cache');
      $fileCheck = clearName($data,false);
      $files = array();
      while(($file = $d->read()) !== false) {
        if(substr($file,0,strlen($fileCheck))==$fileCheck) {
          $json = json_decode(file_get_contents('cache/'.$file));
          if(!isset($json->error)) {
            if(is_object($json)) { $json->file = 'cache/'.$file; }
            if(isset($json->share->share_count) && $json->share->share_count>0) { $files[filemtime('cache/'.$file)] = $json; }
          }

        }
      }
      $max = 0;
      $min = NULL;

      ksort($files);
      foreach($files as $file) {
        if($file->share->share_count>$max) { $max = $file->share->share_count; }
        if($file->share->share_count<$min || $min==NULL) { $min = $file->share->share_count; }
      }
      echo '<div class="graph">';
      $x = 0;
      $curAmount = 0;
      $countUp = 0;
      foreach($files as $mtime => $file) {
        if($file->share->share_count>0) {
          if(($max-$min)!==0) { $height = ((($file->share->share_count-$min)*100)/($max-$min)); } else { $height = 100; }
          if($height<25) { $height = 25; }
          $amount = (-($curAmount-$file->share->share_count));
          if($amount>=0) { $displayAmount = '+'.$amount; } else { $displayAmount = $amount; }
          echo '<div data-value="'.$file->share->share_count.'" title="'.date('r',$mtime).'" data-file="'.$file->file.'" class="graph-line '.($amount>0?'data-more':($amount<0?'data-less':'data-no-change')).'" style="left:'.((100/count($files))*$x).'%;width:'.(100/count($files)).'%; height:'.$height.'%; "><span>'.
          $displayAmount.' ('.$file->share->share_count.')'
          .'</span></div>';
          $curAmount = $file->share->share_count;
          $x++;
        }
      }
      echo '</div>';
    }
    echo '</div>';
    echo '<div class="clear"></div>';
  }
  echo '<p>Updating in <span class="countdown">'.date('i:s',(filemtime($fileName)-strtotime('10 minutes ago'))).'</span>...</p>';
  ?>
  <script type="text/javascript">
  $(document).ready(function() {
    var updateIn = <?php echo (filemtime($fileName)-strtotime('10 minutes ago')); ?>000;
    setTimeout(function(){
      window.location.reload(1);
    }, updateIn);
  });
  </script>
</body>
</html>
