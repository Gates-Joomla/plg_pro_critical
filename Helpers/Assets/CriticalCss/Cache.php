<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	
	
	class Cache
	{
		/**
		 * Установить кэш CACHE_AFTER_RENDER в базу данных
		 * @param $CriticalCssData
		 *
		 * @return bool
		 * @since 3.9
		 */
		public static function setCache( $CriticalCssData ){
			
			try
			{
				$app = \JFactory::getApplication();
			}
			catch( \Exception $e )
			{
			}
			$body = $app->getBody();
			
			
			$db = \JFactory::getDBO ();
			$query = $db->getQuery(true);
			// Поля для обновления
			$fields = array(
				$db->quoteName('cache_after_render') . ' = ' . $db->quote($body)
			);
			// Условия обновления
			$conditions = array(
				$db->quoteName('id') . ' = '  . $db->quote($CriticalCssData['id'] )
			
			);
			$query->update($db->quoteName('#__pro_critical_css'))
				->set($fields)
				->where($conditions);
			//  echo $query->dump();
			
			// Устанавливаем и выполняем запрос
			$db->setQuery($query);
			$db->execute();
			return true ;
		}
	}