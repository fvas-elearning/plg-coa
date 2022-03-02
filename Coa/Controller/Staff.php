<?php
namespace Coa\Controller;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Staff extends \Uni\Controller\AdminManagerIface
{

    /**
     * @var null|\Coa\Db\Coa
     */
    protected $coa = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('Send To Staff');
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {

        $this->coa = \Coa\Db\CoaMap::create()->find($request->get('coaId'));
        if (!$this->coa) {
            \Tk\Alert::addError('Cannot locate Coa object. Please contact your administrator.');
            $this->getConfig()->getBackUrl()->redirect();
        }

        $this->setTable(\Uni\Table\User::create());
        $this->getTable()->init();

        $this->getTable()->removeAction('delete');
        $this->getTable()->removeCell('actions');
        $this->getTable()->removeCell('username');
        $this->getTable()->removeCell('phone');
        $this->getTable()->removeCell('role');
        $this->getTable()->removeCell('active');
        $this->getTable()->removeCell('created');

        if ($this->coa)
            $this->getTable()->prependAction(\Coa\Table\Action\Send::create($this->coa));

        $filter = array(
            'courseId' => $this->getConfig()->getCourseId(),
            'type' => \Uni\Db\User::TYPE_STAFF,
            'active' => true
        );
        $this->getTable()->setList($this->getTable()->findList($filter));

    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->prependTemplate('panel', $this->getTable()->show());
        
        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-certificate" var="panel"></div>
HTML;

        return \Dom\Loader::load($xhtml);
    }


}