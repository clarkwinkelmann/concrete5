<?
namespace Concrete\Core\Page\Controller;
use Page;
use Request;
use Loader;
use Controller;
use Core;
use \Concrete\Core\Page\View\PageView;
class PageController extends Controller {

    protected $supportsPageCache = false;
    protected $action;

    protected $parameters = array();

    public function supportsPageCache() {
        return $this->supportsPageCache;
    }

    public function __construct(Page $c) {
        parent::__construct();
        $this->c = $c;
        $this->view = new PageView($this->c);
        $this->set('html', Core::make('\Concrete\Core\Html\Service\Html'));
    }

    /**
     * Given either a path or a Page object, this is a shortcut to
     * 1. Grab the controller of that page.
     * 2. Grab the view of that controller
     * 3. Render that view.
     * 4. Exit – so we immediately stop all other output in the controller that
     * called render().
     * @param @string|\Concrete\Core\Page\Page $var
     */
    public function render($var)
    {
        if (!($var instanceof \Concrete\Core\Page\Page)) {
            $var = \Page::getByPath($var);
        }

        $controller = $var->getPageController();
        $controller->on_start();
        $controller->runAction('view');
        $controller->on_before_render();
        $view = $controller->getViewObject();
        print $view->render();
        exit;
    }

    public function getPageObject() {
        return $this->c;
    }

    public function getTheme() {
        if (!$this->theme) {
            $theme = parent::getTheme();
            if (!$theme) {
                $theme = $this->c->getCollectionThemeObject();
                if (is_object($theme)) {
                    $this->theme = $theme->getThemeHandle();
                }
            } else {
                $this->theme = $theme;
            }
        }
        return $this->theme;
    }

    public function getRequestAction() {
        return $this->action;
    }

    public function getRequestActionParameters() {
        return $this->parameters;
    }

    public function getControllerActionPath() {
        if (isset($this->controllerActionPath)) {
            return $this->controllerActionPath;
        }

        if (is_object($this->view)) {
            return $this->view->getViewPath();
        }
    }

    public function setupRequestActionAndParameters(Request $request) {
        $task = substr($request->getPath(), strlen($this->c->getCollectionPath()) + 1);
        $task = str_replace('-/', '', $task);
        $taskparts = explode('/', $task);
        if (isset($taskparts[0]) && $taskparts[0] != '') {
            $method = $taskparts[0];
        }
        if ($method == '') {
            if (is_object($this->c) && is_callable(array($this, $this->c->getCollectionHandle()))) {
                $method = $this->c->getCollectionHandle();
            } else {
                $method = 'view';
            }
        }

        try {
            $r = new \ReflectionMethod(get_class($this), $method);
            $cl = $r->getDeclaringClass();
            if (is_object($cl)) {
                if ($cl->getName() != 'Concrete\Core\Controller\Controller' && strpos($method, 'on_') !== 0 && strpos($method, '__') !== 0 && $r->isPublic()) {
                    $foundTask = true;
                }
            }
        } catch(\Exception $e) {

        }

        if ($foundTask) {
            $this->action = $method;
            if (isset($taskparts[1])) {
                array_shift($taskparts);
                $this->parameters = $taskparts;
            }
        } else {
            $this->action = 'view';
            if ($taskparts[0]) {
                $this->parameters = $taskparts;
            }
        }
    }

    public function isValidControllerTask($action, $parameters = array())
    {
        $valid = true;
        if (!is_callable(array($this, $this->action)) && count($this->parameters) > 0) {
            $valid = false;
        }

        if (is_callable(array($this, $this->action))  && (get_class($this) != '\Concrete\Controller\PageForbidden')) {
            // we use reflection to see if the task itself, which now much exist, takes fewer arguments than
            // what is specified
            $r = new \ReflectionMethod(get_class($this), $this->action);
            if ($r->getNumberOfParameters() < count($this->parameters)) {
                $valid = false;
            }
        }
        return $valid;
    }

    public function validateRequest() {

        $valid = true;

        if (!$this->isValidControllerTask($this->action, $this->parameters)) {
            $valid = false;
            // we check the blocks on the page.
            $blocks = $this->getPageObject()->getBlocks();
            foreach($blocks as $b) {
                $controller = $b->getController();
                list($method, $parameters) = $controller->getPassThruActionAndParameters($this->parameters);
                if ($controller->isValidControllerTask($method, $parameters)) {
                    $this->action = 'passthru';
                    $valid = true;
                    break;
                }
            }
        }

        return $valid;
    }
}