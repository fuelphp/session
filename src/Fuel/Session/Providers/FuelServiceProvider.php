<?php
/**
 * @package    Fuel\Session
 * @version    2.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Session\Providers;

use Fuel\Session\Driver;

use Fuel\Dependency\ServiceProvider;


/**
 * FuelPHP ServiceProvider class for this package
 *
 * @package  Fuel\Session
 *
 * @since  1.0.0
 */
class FuelServiceProvider extends ServiceProvider
{
	/**
	 * @var  array  list of service names provided by this provider
	 */
	public $provides = array(
		'session',
		'session.native',
		'session.cookie',
	);

	/**
	 * Service provider definitions
	 */
	public function provide()
	{
		// \Fuel\Session\Manager
		$this->register('session', function ($dic, $config = array())
		{
			// get the session config
			$stack = $this->container->resolve('requeststack');
			if ($request = $stack->top())
			{
				$app = $request->getApplication();
			}
			else
			{
				$app = $this->container->resolve('application.main');
			}

			// check if only a driver name or object is passed
			if ( ! is_array($config))
			{
				$config = array('driver' => $config);
			}

			$config = \Arr::merge($app->getConfig()->load('session', true), $config);

			// determine the driver to load
			if ($config['driver'] instanceOf Driver)
			{
				$driver = $config['driver'];
			}
			elseif (strpos('\\', $config['driver']) !== false and class_exists($config['driver']))
			{
				$class = $config['driver'];
				$driver = new $class($config);
			}
			else
			{
				$driver = $dic->resolve('session.'.$config['driver'], array($config));
			}

			$manager = $dic->resolve('Fuel\Session\Manager', array($driver, $config));

			// start the session
			$manager->start();

			// and use the applications' event instance make sure it ends too
			$app->getEvent()->on('shutdown', function($event) { $this->stop(); }, $manager);

			// return the instance
			return $manager;
		});

		// \Fuel\Session\Driver\Cookie
		$this->register('session.cookie', function ($dic, Array $config = array())
		{
			return $dic->resolve('Fuel\Session\Driver\Cookie', array($config));
		});

		// \Fuel\Session\Driver\Native
		$this->register('session.native', function ($dic, Array $config = array())
		{
			return $dic->resolve('Fuel\Session\Driver\Native', array($config));
		});
	}
}
