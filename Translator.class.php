<?php
/**
 * Translate class for Nette and RoR Copycopter
 * Created: Ondrej Podolinsky @2013 for Ataxo Interactive a.s.
 * Licence: This project is under LGPL license. See LICENSE.txt for further details.
 */
use Nette\Caching\Cache,
    Kdyby\Extension\Curl\Request,
    Nette\Diagnostics\Debugger;

//substituting function for gettext translate in php _()
function t($message){
    return \Translator::getInstance()->translate($message);
}
Class Translator implements  \Nette\Localization\ITranslator
{
    //apiKey for app detection
    protected static $apiKey;
    //default language
    protected static $lang = 'en';
    //link to copycopter
    protected static $url = 'http://copycopter.ataxo.com/api/v2/projects/%s/draft_blurbs';
    //class instance
    protected static $instance = null;
    //array with translates
    protected static $translates = array();
    //calling constructor from config.neon
    protected static $tempLocal = '/../temp';
    /**
     * Initialize of translator called from config.neon
     * @param string $apiKey
     */
    public function __construct($apiKey){
        self::$apiKey = $apiKey;
    }
    /**
     * Set default language
     * @param $lang
     */
    public static function setLang($lang){
        self::$lang = $lang;
    }
    /**
     * Set default url
     * @param string $url
     */
    public static function setUrl($url){
        self::$url = $url;
    }
    /**
     * Set default path to dir with saved cache: __DIR__ . $path;
     * @param string $url
     */
    public static function setTemp($path){
        self::$tempLocal = $path;
    }
    /**
     * singleton initialization of class
     * @return Translator
     */
    public static function getInstance(){
        if(is_null(self::$instance))
            self::$instance = new self(self::$apiKey);
        return self::$instance;
    }
    /**
     * Translate method for latte and forms
     * @param string $message
     * @param null $count
     * @return string
     */
    public function translate($message, $count = null){
        if(count(self::$translates) == 0)
            self::$translates = self::loadTranslates();

        if(array_key_exists($message,self::$translates[self::$lang]))
            $translate = self::$translates[self::$lang][$message];
        else{
            self::sendNewPhrase($message);
            self::addNewPhraseToCache($message);
            $translate = $message;
        }
        //pokud jeste neni prelozena fraze, tak je v prekladu pro jiny jazyk nez byl pri prvnim ulozeni nastaven vychozi text
        if($translate == '')
            $translate = $message;
        return $translate;
    }
    /**
     * Loading translates from cache or copycopter
     * @return array
     */
    public static function loadTranslates(){
        $storage = new \Nette\Caching\Storages\FileStorage(__DIR__ . self::$tempLocal);

        $translates = $storage->read('translates');
        if(is_null($translates)){
            try{
                //added api key to copycopter url
                $url = sprintf(self::$url,self::$apiKey);

                $curl = new Request($url);
                $translatesBase = $curl->get()->getResponse();

                $translates = self::refactorTranslatesBase($translatesBase);
                //save cache
                $storage->write('translates',$translates,array(\Nette\Caching\Cache::EXPIRATION => 3600));
                $storage->write('translatesBackup',array(0 => $translates),array(\Nette\Caching\Cache::EXPIRATION => 3600*24*7));
            }
            catch(Exception $e){
                Debugger::log($e, Debugger::ERROR); // and log exception
                //load translate from backup when some wrong
                $translates = $storage->read('translatesBackup');
                $translates = $translates[0];
            }
        }

        return $translates;
    }
    /**
     * Change basedata from copycopter to php array
     * @param object $base
     * @return array
     */
    public static function refactorTranslatesBase($base){
        $json = json_decode($base);
        $translates = array();
        foreach($json as $key => $val){//example en.key => val
            $lang = substr($key,0,2);//language
            $k = substr($key,3);//key
            $translates[$lang][$k] = $val;
        }
        return $translates;
    }
    /**
     * Send new phrase to copycopter
     * @param $message
     * @return object
     */
    public static function sendNewPhrase($message){
        $url = sprintf(self::$url,self::$apiKey);

        $curl = new Request($url);
        $curl->headers['Content-Type'] = 'application/json';

        $data = '{"'.self::$lang.'.'.$message.'" : "'.$message. '"}';

        return json_decode($curl->post($data)->getResponse());
    }
    /**
     * Add translate to cache and loaded static array with translates
     * @param string $message
     * @return true|Exception
     */
    public static function addNewPhraseToCache($message){
        $storage = new Nette\Caching\Storages\FileStorage(__DIR__ . self::$tempLocal);
        $translates = $storage->read('translatesBackup');
        $translates = $translates[0];
        $translates[self::$lang][$message] = $message;

        self::$translates[self::$lang][$message] = $message;
        $storage->write('translates',$translates,array(\Nette\Caching\Cache::EXPIRATION => 3600));
        $storage->write('translatesBackup',array(0 => $translates),array(\Nette\Caching\Cache::EXPIRATION => 3600));
        return true;
    }
}