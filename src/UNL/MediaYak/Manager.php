<?php
class UNL_MediaYak_Manager implements UNL_MediaYak_CacheableInterface, UNL_MediaYak_PostRunReplacements
{
    /**
     * The auth object.
     *
     * @var UNL_Auth
     */
    protected $auth;
    
    /**
     * The user that's logged in.
     *
     * @var UNL_MediaYak_User
     */
    protected static $user;
    
    public $output;
    
    public $options = array('view'=>'addmedia');

    protected $view_map = array(
        'feedmetadata'    => 'UNL_MediaYak_Feed_Form',
        'permissions'     => 'UNL_MediaYak_Feed_UserList',
        'feeds'           => 'UNL_MediaYak_User_FeedList',
        'subscriptions'   => 'UNL_MediaYak_User_Subscriptions',
        'addsubscription' => 'UNL_MediaYak_Subscription_Form',
        );
    
    protected static $replacements = array();
    
    public static $url;
    
    /**
     * MediaYak
     *
     * @var UNL_MediaYak
     */
    protected $mediayak;
    
    function __construct($options = array(), $dsn)
    {
        $this->mediayak = new UNL_MediaYak($dsn);
        
        $this->auth = UNL_Auth::factory('SimpleCAS');
        $this->auth->login();
        if (isset($_GET['logout'])) {
            $this->auth->logout();
            exit();
        }
        
        $this->options = $options + $this->options;
        
        self::$user = UNL_MediaYak_User::getByUid($this->auth->getUser());
    }
    
    function getCacheKey()
    {
        return false;
    }
    
    function preRun($cached)
    {
        return true;
    }
    
    /**
     * Allows you to set dynamic data when cached output is sent.
     *
     * @param string $field Area to be replaced
     * @param string $data  Data to replace the field with.
     *
     * @return void
     */
    function setReplacementData($field, $data)
    {
        switch ($field) {
        case 'title':
        case 'head':
        case 'breadcrumbs':
            self::$replacements[$field] = $data;
            break;
        }
    }
    
    /**
     * Called after run - with all output contents.
     *
     * @param string $me The content from the outputcontroller
     *
     * @return string
     */
    function postRun($me)
    {
        $scanned = new UNL_Templates_Scanner($me);
        
        if (isset(self::$replacements['title'])) {
            $me = str_replace($scanned->doctitle,
                              '<title>'.self::$replacements['title'].'</title>',
                              $me);
        }
        
        if (isset(self::$replacements['head'])) {
            $me = str_replace('</head>', self::$replacements['head'].'</head>', $me);
        }

        if (isset(self::$replacements['breadcrumbs'])) {
            $me = str_replace($scanned->breadcrumbs,
                              self::$replacements['breadcrumbs'],
                              $me);
        }
        
        return $me;
    }
    
    function run()
    {
        try {
            if (count($_POST)) {
                $this->handlePost();
            }

            switch($this->options['view']) {
                case 'feed':
                    $this->showFeed();
                    break;
                case 'feedmetadata':
                case 'permissions':
                case 'feeds':
                case 'subscriptions':
                case 'addsubscription':
                    $class = $this->view_map[$this->options['view']];
                    $this->output[] = new $class($this->options);
                    break;
                case 'addmedia':
                    $this->addMedia();
                    // intentional no break
                default:
                    $class = $this->view_map['feeds'];
                    $this->output[] = new $class($this->options);
                    break;
            }
        } catch (Exception $e) {
            $this->output = $e;
        }
    }
    
    /**
     * Determines if the user is logged in.
     *
     * @return bool
     */
    function isLoggedIn()
    {
        return $this->auth->isLoggedIn();
    }
    
    /**
     * Get the user
     *
     * @return UNL_MediaYak_User
     */
    public static function getUser()
    {
        return self::$user;
    }
    
    function showMedia(UNL_MediaYak_Filter $filter = null)
    {
        $options           = $this->options;
        $options['filter'] = $filter;

        $this->output[] = new UNL_MediaYak_MediaList($options + $this->options);
    }
    
    public static function getURL($mixed = null, $additional_params = array())
    {
        $params = array();

        if (is_object($mixed)) {
            switch(get_class($mixed)) {
                case 'UNL_MediaYak_Feed':
                    $params['view'] = 'feed';
                    $params['id']   = $mixed->id;
            }
        }

        $params = array_merge($params, $additional_params);

        return UNL_MediaYak_Controller::addURLParams(UNL_MediaYak_Controller::$url.'manager/', $params);
    }
    
    function showFeed()
    {
        $feed = UNL_MediaYak_Feed::getById($_GET['id']);
        if (!($feed && $feed->userHasPermission(self::$user, UNL_MediaYak_Permission::getByID(
                                                    UNL_MediaYak_Permission::USER_CAN_INSERT)))) {
            throw new Exception('You do not have permission for this feed.');
        }

        $this->output[] = $feed;

        $filter = new UNL_MediaYak_MediaList_Filter_ByFeed($feed);
        $this->showMedia($filter);

    }
    
    /**
     * This function accepts info posted to the system.
     *
     */
    function handlePost()
    {
        $handler = new UNL_MediaYak_Manager_PostHandler($this->options, $_POST, $_FILES);
        $handler->setMediaYak($this->mediayak);
        return $handler->handle();
    }
    
    function editFeedPublishers($feed)
    {
    }
    
    /**
     * Show the form to add media to a feed.
     *
     * @return void
     */
    function addMedia()
    {
        if (isset($_GET['id'])) {
            $this->output[] = new UNL_MediaYak_Feed_Media_Form(UNL_MediaYak_Media::getById($_GET['id']));
            return;
        }
        
        $this->output[] = new UNL_MediaYak_Feed_Media_Form();
    }
}
