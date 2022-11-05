<?php
  //
  //  Script to read user_timeline data and post tweets
 //
  class  application
  {
    public $twitter_consumer_key = '<consumer-key>';
    public $twitter_consumer_secret = '<consumer-secret>';
    public $twitter_access_token = '<ccess_token>';
    public $twitter_access_token_secret = '<token-secret>';
    public $twitter_version = '1.0';
    public $sign_method = 'HMAC-SHA1';
    public $facebook_page_id='1387602394895539';
    public $facebook_page_access_token="<page-access-token>";
    public $facebook_group_id='751452211639661';
    public $facebook_group_access_token="<group-access-token>";
    public static $serverName="localhost";
    public static $userName = "<username>";
    public static $password = "<password>";
    public static $dbname = "social_media";
    //
    //  Method to read Twitter user_timeline data
    //
    function scanTwitter($url,$query)
    {
      //Collecting parameters 
      $oauth = array(
          'oauth_consumer_key' => $this->twitter_consumer_key,
          'oauth_token' => $this->twitter_access_token,
                    // a stronger nonce is recommended
          'oauth_nonce' => (string)mt_rand(),
          'oauth_timestamp' => time(),
          'oauth_signature_method' => $this->sign_method,
          'oauth_version' => '1.0'
      );
      $oauth = array_map("rawurlencode", $oauth); // must be encoded before sorting
      $query = array_map("rawurlencode", $query);
      $arr   = array_merge($oauth, $query); // combine the values THEN sort

      //Sort list of parameters alphbetically
      asort($arr); // secondary sort (value)
      ksort($arr); // primary sort (key)

      $querystring = urldecode(http_build_query($arr, '', '&'));
      //Ceate a signature Base String
      $base_string = 'GET' . "&" . rawurlencode($url) . "&" . rawurlencode($querystring);
      $key = rawurlencode($this->twitter_consumer_secret) . "&" . rawurlencode($this->twitter_access_token_secret);
      $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base_string, $key, true)));

      $url.= "?" . $querystring;
      $url         = str_replace("&amp;", "&", $url);

      $oauth['oauth_signature'] = $signature;
      //Building Header string  
      $auth = "OAuth " . urldecode(http_build_query($oauth, '', ', '));
      $options = array(CURLOPT_HTTPHEADER => array("Authorization: $auth"),
            //Twitter
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false);
      $feed = curl_init();
      curl_setopt_array($feed, $options);
      $twitter_response = curl_exec($feed);
      $twitter_data = json_decode($twitter_response);
      curl_close($feed);
       $conn = mysqli_connect(self::$serverName,self::$userName,self::$password,self::$dbname);

      foreach($twitter_data as $index1 => $val1)
        {
         $tweet_id=$val1['id_str'];
         $created_at=$val1['created_at'];
         $text=$val1['text'];
         $reply_to_status_id=$val1['in_reply_to_status_id'];
         $reply_to_user_id=$val1['in_reply_to_user_id'];
         $reply_to_screen_name=$val1['in_reply_to_screen_name'];				
         $retweet_count=$val1['retweet_count'];
         $fovorite_count=$val1['favorite_count'];

         $sql="INSERT INTO twitter_scan(
	 		tweet_id,
	 		created_at,
			text,reply_to_user_id,
			reply_to_screen_name,
			retweet_count,
			favorite_count) 
			
			VALUES(
			'$tweet_id','$created_at','$text',
			'$reply_to_user_id',
			'$reply_to_screen_name',
			'$retweet_count',
			'$fovorite_count')";
         $conn->query($sql); 
         foreach($val1 as $index2 => $v2)    //entities
         { 
            if($index2 == 'user')
            {
              foreach($v2 as $vv2)
              {
                $posted_by=$v2['screen_name'];
                $sql="UPDATE twitter_scan SET posted_by = '$posted_by' WHERE tweet_id = $tweet_id";
                $conn->query($sql);

              }
            }

            else
            {
	
         	
           if ($index2 == 'entities')  
             {
               foreach($v2 as $index3 => $v3) //entities-hashtags
               { 
                 switch($index3)
                 {
                   case 'user_mentions': 
                      foreach ($v3 as $index4 => $v4) 
                      {
                          $screen_name=$v4['screen_name'];
                          $sql="UPDATE twitter_scan SET user_mention_id = '$screen_name' WHERE tweet_id = $tweet_id";             
                          $conn->query($sql);   
                        }
                    break;
                   case 'hashtags':  
                         foreach ($v3 as $index4 => $v4) 
                        {
                          $hashtag_text=$v4['text'];
                          $sql="UPDATE twitter_scan SET hashtag_text = '$hashtag_text' WHERE tweet_id = $tweet_id";         
                          $conn->query($sql);                    
                        }
                    break;
                    case 'urls':
                         foreach ($v3 as $index4 => $v4) 
                        {
                          $url=$v4['expanded_url'];
                          $sql="UPDATE twitter_scan SET url = '$url' WHERE tweet_id = $tweet_id";      
                          $conn->query($sql);
                        }
                    break;
                }
              }
            }
          }
        }
       }   $conn->close();
    } //scanTwitter()
    //
    // Method to read Facebook Page and Group Posts
    //
    function scanFacebook()
    {
      //facebook page scan
      $fb_page_feed="https://graph.facebook.com/{$this->facebook_page_id}/feed?access_token=".$this->facebook_page_access_token;
      $feed = curl_init();
      curl_setopt($feed, CURLOPT_URL, $fb_page_feed);
      curl_setopt($feed, CURLOPT_REFERER, '');
      curl_setopt($feed, CURLOPT_ENCODING, 'gzip,deflate');
      curl_setopt($feed, CURLOPT_AUTOREFERER, true);
      curl_setopt($feed, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($feed, CURLOPT_TIMEOUT, 10);
       $fb_page_response = json_decode(curl_exec($feed));
 $conn = mysqli_connect(self::$serverName,self::$userName,self::$password,self::$dbname);

      foreach($fb_page_response as $index=>$val)
      {
         if($index == 'data')
         { 
          foreach($val as $index1=>$val1)
         { 
           $group_status_id=$val1['id'];
           $group_status_message=$val1['message'];
           $created_time=$val1['created_time'];
           $updated_time=$val1['updated_time'];
           $type=$val1['type']; 
                    
           $sql="INSERT INTO facebook_scan(post_id,message,type,created_at,updated_at) VALUES ('$group_status_id','$group_status_message','$type','$created_time','$updated_time') ";           
           $conn->query($sql);
           foreach($val1 as $index2=>$val2)
           {
              switch($index2)
             {               
                case 'from': 
                  foreach($val2 as $index3=>$val3)
                  {
                    $from_name=$val2['name'];
                    $sql="UPDATE facebook_scan SET from_name='$from_name' WHERE post_id='$group_status_id'";
                    $conn->query($sql);
                 } 
                 break;
                case 'privacy':
                 foreach($val2 as $index3=>$val3)
                 { 
                   $privacy_value=$val2['value'];
                   $privacy_description=$val2['description'];
                   $sql="UPDATE facebook_scan SET privacy_value='$privacy_value',privay_description='$privacy_description' WHERE post_id='$group_status_id'";
                   $conn->query($sql);
                 }
                 break;
             case 'application':  
                 foreach($val2 as $index3 => $val3)
                 {
                   $application_name=$val2['name'];
                   $sql="UPDATE facebook_scan SET application_name='$application_name' WHERE post_id='$group_status_id'";
                   $conn->query($sql);
                 }  
               break; 
               }
             } 
            }
          }            
        }  
      //Facebook Group scan
      $group_url="https://graph.facebook.com/{$this->facebook_group_id}/feed?access_token=".$this->facebook_group_access_token;
      $feed=curl_init();
      curl_setopt($feed, CURLOPT_URL, $group_url);
      curl_setopt($feed, CURLOPT_REFERER, '');
      curl_setopt($feed, CURLOPT_ENCODING, 'gzip,deflate');
      curl_setopt($feed, CURLOPT_AUTOREFERER, true);
      curl_setopt($feed, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($feed, CURLOPT_TIMEOUT, 10);

      $fb_group_response = json_decode(curl_exec($feed));
      foreach($fb_group_response as $index=>$val)
      {
         if($index == 'data')
         { 
          foreach(array_slice($val, 0, sizeof($val)-1)  as $index1=>$val1)
         { 
           $group_status_id=$val1['id'];
           $group_status_message=$val1['message'];
           $created_time=$val1['created_time'];
	   $updated_time=$val1['updated_time'];
           $type=$val1['type'];	
                    
           $sql="INSERT INTO facebook_scan(post_id,message,type,created_at,updated_at) VALUES ('$group_status_id','$group_status_message','$type','$created_time','$updated_time') ";           
           $conn->query($sql);
           foreach($val1 as $index2=>$val2)
           {
              switch($index2)
             {               
                case 'from': 
                  foreach($val2 as $index3=>$val3)
                  {
                    $from_name=$val2['name'];
                    $sql="UPDATE facebook_scan SET from_name='$from_name' WHERE post_id='$group_status_id'";
                    $conn->query($sql);
                 } 
                 break;
                case 'privacy':
                 foreach($val2 as $index3=>$val3)
                 { 
                   $privacy_value=$val2['value'];
                   $privacy_description=$val2['description'];
                   $sql="UPDATE facebook_scan SET privacy_value='$privacy_value',privay_description='$privacy_description' WHERE post_id='$group_status_id'";
                   $conn->query($sql);
                 }
                 break;
             case 'application':  
                 foreach($val2 as $index3 => $val3)
                 {
                   $application_name=$val2['name'];
                   $sql="UPDATE facebook_scan SET application_name='$application_name' WHERE post_id='$group_status_id'";
                   $conn->query($sql);
                 }  
               break; 
               }
             } 
            }
          }            
        }  
     $conn->close();
     curl_close($feed);
     } //scanFacebook()
    //
    // Method to post data to Twitter
    // 
       function postTwitter($status)
    {
      $url = 'https://api.twitter.com/1.1/statuses/update.json';
      $param_string = 'oauth_consumer_key=' . $this->twitter_consumer_key .
            '&oauth_nonce=' . time() .
            '&oauth_signature_method=' . $this->sign_method .
            '&oauth_timestamp=' . time() .
            '&oauth_token=' . $this->twitter_access_token .
            '&oauth_version=' . $this->twitter_version .
            '&status=' . rawurlencode($status);

       //Generate a signature base string for POST
        $base_string = 'POST&' . rawurlencode($url) . '&' . rawurlencode($param_string);
        $sign_key = rawurlencode($this->twitter_consumer_secret) . '&' . rawurlencode($this->twitter_access_token_secret);
        //Generate a unique signature
        $signature = base64_encode(hash_hmac('sha1', $base_string, $sign_key, true));
        $curl_header = 'OAuth oauth_consumer_key=' . rawurlencode($this->twitter_consumer_key) . ',' .
            'oauth_nonce=' . rawurlencode(time()) . ',' .
            'oauth_signature=' . rawurlencode($signature) . ',' .
            'oauth_signature_method=' . $this->sign_method . ',' .
            'oauth_timestamp=' . rawurlencode(time()) . ',' .
            'oauth_token=' . rawurlencode($this->twitter_access_token) . ',' .
            'oauth_version=' . $this->twitter_version;

        $ch = curl_init();
        //Twitter post
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $curl_header));
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, 'status=' . rawurlencode($status));
        curl_setopt($ch, CURLOPT_URL, $url);
        $twitter_post = json_decode(curl_exec($ch));
        curl_close($ch);
        print_r($twitter_post);
      }
      //
      // Method to post data to Facebook Page and Group
      // 
      function postFacebook($status)
      {
        //Facebook page post
        $fb_page_post="https://graph.facebook.com/{$this->facebook_page_id}/feed?message=$status&access_token=".$this->facebook_page_access_token;
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $fb_page_post);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_REFERER, '');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $fb_page_post = json_decode(curl_exec($ch));
     }//postFacebook()
    //
    //  Select whether you want to Scan or Post through commandline
    //
    public function select()
    {
       global $argc, $argv;
       if ($argc == 1 || $argc > 3)
       {
         printf("Incorrect Arguments".PHP_EOL);
       }
       else
       {
         if($argv[1]=='search')
         {
              $url='https://api.twitter.com/1.1/search/tweets.json';
              printf("Enter Hashtag to be scanned".PHP_EOL);
              $query = readline();
              $this->scan($url,$query);
         }
         else
         {
           if($argv[1]=='scan')
           {
             printf("Select medias to scan".PHP_EOL);
             printf("Twitter".PHP_EOL.
                    "Facebook".PHP_EOL
              .PHP_EOL);
             $media=array();
             $media[]=readline();
             $media_count=sizeof($media);
             for($i=0;$i<$media_count;$i++)
             {
               switch($media[$i])
               {
                 case 'Twitter':
                   $url='https://api.twitter.com/1.1/statuses/user_timeline.json';
                   $query = array(// query parameters
                          'q' => NULL,
                       );
                   $this->scanTwitter($url,$query);
                   break;
                 case 'Facebook':
                   $this->scanFacebook();
                   break;

                 default:
                   printf("Enter a valid selection");
                }
              }
            }
             else
             {
               if($argv[1]=='post')
               {
                  printf("Enter text to be posted".PHP_EOL) ;
                  $status=readline();
                  printf("Select medias to post" .PHP_EOL);
                  printf("twitter".PHP_EOL.
                   "facebook
                         ");
                  $media=array();
                  $media[]=readline();
                  $media_count=sizeof($media);
                  for($i=0;$i<$media_count;$i++)
                  {
                    switch($media[$i])
                    {
                      case 'twitter':
                            $this->postTwitter($status);
                            break;
                      case 'facebook':
                            $this->postFacebook($status);
                            break;
                      default:
                            printf("Enter a valid selection");
                    }
                  }
                }
                else
                {
                   printf("Invalid argument value");
                }
              }
            }
          }
        }
      }
$service = new application();
$service->select();
?>
