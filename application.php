<?php

  //
  //  Script to read user_timeline data and post tweets
  //

  class  application
  {
    public $twitter_consumer_key = 'kDzo5MYbi8lQDxUjYsENjvrhm';
    public $twitter_consumer_secret = '27BmsrY6VIJdF8VGztdboY5s4fP3p87aRwE0peY1GYvCuXEadU';
    public $twitter_access_token = '2534471005-C0AWIkzouNX1rMd9k9suYONgXMpLhVcBhdS9g75';
    public $twitter_access_token_secret = 'ftuz6qnvzVkUEHJrvKPC0R0sNFInBqZruKDADiAAI413a';
    public $twitter_version = '1.0';
    public $sign_method = 'HMAC-SHA1';
    public $facebook_page_id='1387602394895539';
    public $facebook_page_access_token="CAAI7NRi2RJABAEKHDiXB9ZAgsqYiQCF2Y17KvZBNvsRBwuLCS4jj3w5c0mK6B7XFVM6VgjdOcQMaVUpVJEs2WYPviZBNOgmXabdtkaSIkpJd6mgGlelN7EwZBkWtzjiLW0mHptLs0LdpN1HPNPVmJ6FCKaoqZBDmiY15PxZAt8vHImrV9et9bg";
    public $facebook_group_id='751452211639661';
    public $facebook_group_access_token="CAAI7NRi2RJABAOCZBb4JxfDwj0k8wbsvzRHSoTw6lZAk8E8DmsJuPwZBiidxiZABEKfeSqAxnmsreFrUYkxJtjDpcxpEu4fYgJFoLHZCGnFa4NCrpuomzk97KjbY9AxZCGZB4KfhgLXvtsfLWTwzMKdFIXuDKjBm0vdlAZBNkpLaZABqk9B9FecPRbnF84hZCXxTcZD";


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
      printf(PHP_EOL."----Twitter data----".PHP_EOL);
      print_r($twitter_data);

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
      curl_close($feed);


      //Display Responses

      printf("----Facebook group data----");
     print_r($fb_group_response);

      printf("----Facebook Page data-----");
      print_r($fb_page_response);

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
                                                                                             305,1         Bot
                                                                           273,1         88%
                                                                                         
