<?php
/**
 * @copyright 2021 Arawa <TODO>
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\Workspace\AppInfo;

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\App;
use OCP\IServerContainer;
use OCP\Notification\IManager;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Notification\Notifier;
use OCA\Workspace\Middleware\IsSpaceAdminMiddleware;
use OCA\Workspace\Middleware\IsGeneralManagerMiddleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCP\AppFramework\Bootstrap\IBootContext;

class Application extends App {

        public const APP_ID = 'workspace';
	    public const GROUP_WKSUSER = 'WorkspacesManagers';	// Group that holds all workspace users (members managed by the application)
        public const GENERAL_MANAGER = "GeneralManager";	// Group that holds the application administrators
	    // TODO Remove the '_01' suffix 
        public const ESPACE_MANAGER_01 = "GE-";
        public const ESPACE_USERS_01 = "U-";
        public const GID_SPACE = "SPACE-";


        public function __construct(array $urlParams=[] ) {
                parent::__construct(self::APP_ID, $urlParams);

                $container = $this->getContainer();

                $container->registerService('WorkspaceAccessControlMiddleware', function($c){
                    return new WorkspaceAccessControlMiddleware(
                        $c->query(IURLGenerator::class),
                        $c->query(UserService::class)
                    );
                });

                $container->registerService('IsSpaceAdminMiddleware', function($c){
                    return new IsSpaceAdminMiddleware(
                        $c->query(IControllerMethodReflector::class),
                        $c->query(IRequest::class),
                        $c->query(UserService::class)
                    );
                });

                $container->registerService('IsGeneralManagerMiddleware', function($c){
                    return new IsGeneralManagerMiddleware(
                        $c->query(IControllerMethodReflector::class),
                        $c->query(IRequest::class),
                        $c->query(UserService::class)
                    );
                });

                $container->registerMiddleware('OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware');
                $container->registerMiddleware('OCA\Workspace\Middleware\IsSpaceAdminMiddleware');
        }

        public function boot(IBootContext $context): void {
            $server = $context->getServerContainer();

            $this->registerNotifier($server);
        }

        protected function registerNotifier(IServerContainer $server): void {
            $manager = \OC::$server->get(IManager::class);
            $manager->registerNotifierService(Notifier::class);    
        }
}
