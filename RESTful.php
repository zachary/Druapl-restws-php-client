<?php
class RESTful {
  private $base_url=null;
  private $token=null;
  private $curl=null;
  public function __construct($url){
    $this->base_url=$url;
    $this->curl = curl_init();
    $this->login();
  }
  private function login() {
    curl_setopt($this->curl, CURLOPT_URL, $this->base_url.'/restws/session/token');
    curl_setopt($this->curl, CURLOPT_USERPWD, "restwsuser:password");
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_HEADER, 1);

    // Parse the header to get the token and cookie
    $result = curl_exec($this->curl);
    $token = substr($result, (strlen($result) - 43));
    //preg_match('/^Set-Cookie:\s*([^;]*)/mi', $result, $m);
    //$tihs->cookie = $m[1];
    $this->token= $token;
  }
  public function getAll($type) {
    if(empty($this->token)) return 'error';
    else {
      $headers[] = 'Content-type: application/json';
      $headers[] = "X-CSRF-Token: $this->token";
      //$headers[] = "Cookie: $cookie";
      curl_setopt($this->curl, CURLOPT_URL, $this->base_url.'/node.json?type='.$type);
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($this->curl, CURLOPT_HEADER, 0);
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      $json = curl_exec($this->curl);
      return json_decode($json);
    }
  }
  public function get($id,$keyword) {
    $headers[] = 'Content-type: application/json';
    $headers[] = "X-CSRF-Token: $this->token";
    //$headers[] = "Cookie: $this->cookie";
    curl_setopt($this->curl, CURLOPT_URL, $this->base_url."/node.json?$keyword=$id");
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($this->curl, CURLOPT_HEADER, 0);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($this->curl, CURLINFO_HEADER_OUT, TRUE);
    $json = curl_exec($this->curl);
    return json_decode($json);
  }
  public function post($type,$data) {
    $json = json_encode($data);

    $headers[] = 'Content-type: application/json';
    $headers[] = "X-CSRF-Token: $this->token";
    //    curl_setopt($this->curl, CURLOPT_URL,  $this->base_url . '/'.$type);
    curl_setopt($this->curl, CURLOPT_URL, $this->base_url.'/node.json?type='.$type);
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($this->curl, CURLOPT_POST, TRUE);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_HEADER, 0);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($this->curl);
    return $response;
  }
  public function put($id,$data) {
    $json = json_encode($data);

    $headers[] = 'Content-type: application/json';
    $headers[] = "X-CSRF-Token: $this->token";
    //    curl_setopt($this->curl, CURLOPT_URL,  $this->base_url . '/'.$type);
    curl_setopt($this->curl, CURLOPT_URL, $this->base_url."/node/$id");
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($this->curl, CURLOPT_POST, TRUE);
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_HEADER, 0);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($this->curl);
    return $response;
  }
  public function delete($id){
    if(empty($this->token)) return 'error';
    else {
      $headers[] = 'Content-type: application/json';
      $headers[] = "X-CSRF-Token: $this->token";
      //$headers[] = "Cookie: $cookie";
      curl_setopt($this->curl, CURLOPT_URL, $this->base_url."/node/$id");
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($this->curl, CURLOPT_HEADER, 0);
      curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
      $json = curl_exec($this->curl);
      return $json;
    }
  }
  public function __destruct(){
    curl_close($this->curl);
  }
}

$r= new RESTful("http://localhost/drupal7");
echo $r->get('B0073Z88WM','field_asin')->list[0]->nid;
exit;
/*----------------------------delete node from node id---------------------
for ($i = 1212; $i <= 1809; $i++) {
  $r->delete($i);
}
*/

//--------------------for mysql---------------------------------------
require_once __DIR__."/myTools.php";
$mt=new MyTools(['sqldb'=>'mysql','dbname'=>'amazon_ebay']);
$data=$mt::$sqldb->query("SELECT * from amazon_asin a left join amazon_items i on a.asin=i.asin where i.price>0 ")->fetchAll(PDO::FETCH_ASSOC);
//print_r($data);
foreach ($data as $v) {
  $articles=array('type'=>'amazon_items',
    //'body'=>array('value'=>'kadsfasfd','format'=>'filtered_html',),
    'body'=>array('value'=>html_entity_decode($v['description'],ENT_QUOTES,"UTF-8"),'format'=>'full_html',),
    //'field_oc_blog_topic'=>array('id'=>13),
    'title'=>$v['title'],
    'field_rank'=>$v['rank'],
    'field_asin'=>$v['asin'],
    'field_manufacturer'=>$v['manufacturer'],
    'field_upc'=>$v['upc'],
    'field_title'=>$v['title'],
    'field_star'=>$v['star'],
    'field_price'=>$v['price'],
    'field_reviews'=>$v['reviews'],
    'field_desc'=>$v['desc1'],
    'field_weight'=>$v['weight'],
    'field_dimension'=>$v['dimension'],
    'field_mtime'=>$v['mtime'],
    'author'=>2,
  );
  //print($r->put('1762',$articles));
  print($r->post('node',$articles));
  print("\n");
}
exit;
//$r= new RESTful("http://localhost/drupal7");
//print($r->get('sprint_of_desert'));
//$user_info = array(
//  'name' => 'newuser5',
//  'status' => 1,
//  'mail' => 'newuser5@yahoo.com',
//);
//print($r->put('user',$user_info));
//exit;
/*
$articles=array('type'=>'openchurch_blog',
  'body'=>array('value'=>'kadsfasfd','format'=>'filtered_html',),
  'field_oc_blog_topic'=>array('id'=>13),
  'title'=>'rest test',
  'author'=>2,
);
print($r->post('node',$articles));
exit;
 */
 
//------------------------------------for mongodb---------------------
$m = new MongoClient();
$db=$m->selectDB('test');
$c=$db->selectCollection("JesusLovesUs");
$cursor=$c->find();
foreach ($cursor as $doc) {
  $title=$body=$cbody='';
  array_shift($doc);
  foreach($doc as $k=>$v){
    $title=substr($k,-5);
    //$body=$v['DP'][1]['en'];
    //$cbody=$v['DP'][0]['cn'];
    $body=$v['SOD'];
  }
  $articles=array('type'=>'spring_of_desert',
    'body'=>array('value'=>$body,'format'=>'filtered_html',),
    'title'=>$title,
    'author'=>2,
  );
  /*
  $articles=array('type'=>'daily_proverbs',
    'body'=>array('value'=>$body,'format'=>'filtered_html',),
    'field_cn_body'=>array('value'=>$cbody,'format'=>'filtered_html',),
    'title'=>$title,
    'author'=>2,
  );
   */
  print($r->post('node',$articles));
}
