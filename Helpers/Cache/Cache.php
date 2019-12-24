<?php
	
	namespace Plg\Pro_critical\Helpers\Cache;
	
	use Plg\Pro_critical\Components\Url;
	
	class Cache
	{
		/**
		 * Cache instance.
		 *
		 * @var    JCache
		 * @since  1.5
		 */
		public $_cache;
		
		/**
		 * Cache key
		 *
		 * @var    string
		 * @since  3.0
		 */
		public $_cache_key;
		private $app;
		public static $instance;
		public static $CacheKey = null;
		public static $Router_Data = null;
		
		
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = \JFactory::getApplication();
			// Set the cache options.
			$options = array(
				'defaultgroup' => 'page',
				'browsercache' => false,
				'caching'      => false,
			);
			
			// Instantiate cache with previous options and create the cache key identifier.
			$this->_cache     = \JCache::getInstance('page', $options);
			$this->_cache_key = \JUri::getInstance()->toString();
			
			
			
			/*echo'<pre>';print_r( $this->_cache );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $this->_cache_key );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );*/
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Cache
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function instance ( $options = [] )
		{
			if( self::$instance === null )
			{
				self::$instance = new self( $options );
			}
			return self::$instance;
		}#END FN
		
		
		
		
		
		
		
		
		/**
		 * Получить MD5 ключ кэша
		 * @throws \Exception
		 * @since 3.9
		 *
		 */
		/*public function getCacheKey()
		{
			
			if( self::$CacheKey ) return self::$CacheKey; #END IF
			
			$uri = \JUri::getInstance();
			$router            = $this->app->getRouter();
			self::$Router_Data = $router->parse( $uri );
			$uri::reset();
			
			
			
			if( self::$Router_Data['format'] != 'html') {
				self::$CacheKey = null ;
				return self::$CacheKey ;
			} #END IF
			
			
//			$Component               = \Plg\Pro_critical\Components\Component::instance();
//			$Router_Data[ 'option_id' ] = $Component->getOptionId();
//			$Router_Data[ 'view_id' ]   = $Component->getViewId();
			self::$CacheKey          = md5( json_encode( self::$Router_Data ) );
//			echo'<pre>';print_r( $Router_Data );echo'</pre>'.__FILE__.' '.__LINE__;
			return self::$CacheKey;
			
		}*/
		
		
		/**
		 * Получить ключ кеша для текущей страницы на основе URL-адреса и других возможных факторов.
		 *
		 * @return  string
		 *
		 * @throws \Exception
		 * @since   3.7
		 */
		public function getCacheKey()
		{
			static $key;
			if (!$key)
			{
				//\JPluginHelper::importPlugin('pagecache');
				//$parts = \JEventDispatcher::getInstance()->trigger('onPageCacheGetKey');
				
				$parts[] = \JUri::getInstance()->toString();
				$key = md5(serialize($parts));
			}
			return $key;
		}
	
		/**
		 * Получение кэша AfterInitialise
		 * Если кэшь создан - отправляем Ползователю закрываем приложение
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getCache( $type = 'body' ){
			$CacheKey = $this->getCacheKey();
			
			$config = \Joomla\CMS\Factory::getConfig();
			$caching_config = $config->get('caching');
			
			$this->_cache->setCaching($caching_config);
			$data = $this->_cache->get( $CacheKey );
			
			// If page exist in cache, show cached page.
			if ($data !== false)
			{
				// Set HTML page from cache.
				$this->app->setBody( $data );
				// Dumps HTML page.
			    echo $this->app->toString( (bool) $this->app->get('gzip')  );
				// Mark afterCache in debug and run debug onAfterRespond events.
				// e.g., show Joomla Debug Console if debug is active.
			/*	if (JDEBUG)
				{
					\JProfiler::getInstance('Application')->mark('afterCache');
					\JEventDispatcher::getInstance()->trigger('onAfterRespond');
				}*/
				// Closes the application.
				$this->app->close();
			}
		}
		
		public function AfterRender(){
			if ($this->_cache->getCaching() === false)
			{
				return;
			}
			// Disable compression before caching the page.
			$this->app->set('gzip', false);
			
		/*	echo'<pre>';print_r(  $this->getCacheKey() );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $this->_cache );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );*/
		}
		
		
		/**
		 * Установка Кэша
		 * @return void
		 * @throws \Exception
		 * @since 3.9
		 *
		 */
		
		public function setCache(){
			
			if ($this->_cache->getCaching() === false) return; #END IF
			
			// Saves current page in cache.
			$this->_cache->store( $this->app->getBody(), $this->getCacheKey() );
			
			
			
			
			
			/*if( !self::$CacheKey ) return false; #END IF
			
			$app = \JFactory::getApplication() ;
			$body = $app->getBody();
			
			if( mb_strlen ($body) < 100 ) return false ; #END IF
			
			
			$hash = $this->getCacheKey();
			
			$url_id = Url::instance()->getId() ;
			
			$db = \JFactory::getDbo() ;
			$query = $db->getQuery(true);
			
			$table = $db->quoteName('#__pro_critical_cache') ;
			
			$columns = array('cache','hash','pro_critical_url_id','type' );
			$values =
				$db->quote( $body ).","
				.$db->quote( $hash ).","
				.$db->quote( $url_id ).","
				.$db->quote( 'body' ) ;
			
			$query->values(  $values );
			
			$query->insert( $table )->columns( $db->quoteName( $columns ) );
			
			$db->setQuery($query);
			echo $query->dump();
			$db->execute();
			
			# Id - Вствленной стороки
			$new_id = $db->insertid();
			
			return true ;*/
		}
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	