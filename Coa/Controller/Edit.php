<?php
namespace Coa\Controller;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends \Uni\Controller\AdminEditIface
{
    /**
     * @var \Coa\Db\Coa
     */
    protected $coa = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setPageTitle('COA Edit');
    }

    /**
     * @param \Tk\Request $request
     * @return mixed
     * @throws \Exception
     */
    public function doDefault(\Tk\Request $request)
    {
        $this->coa = new \Coa\Db\Coa();
        $this->coa->setCourseId($this->getConfig()->getCourseId());
        if ($request->get('coaId')) {
            $this->coa = \Coa\Db\CoaMap::create()->find($request->get('coaId'));
        }

        if ($request->has('preview')) {
            return $this->doPreview($request);
        }

        $this->setForm(\Coa\Form\Coa::create()->setModel($this->coa));
        $this->getForm()->execute();
    }

    /**
     * @param \Tk\Request $request
     * @return mixed
     * @throws \Exception
     */
    public function doPreview(\Tk\Request $request)
    {
        /** @var \Coa\Adapter\Iface $adapter */
        $adapter = null;
        switch ($this->coa->getType()) {
            case 'supervisor':
                $supervisor = \App\Db\SupervisorMap::create()->findCpdTotals(
                    array('subjectId' => $this->getConfig()->getSubjectId(), 'status' => \App\Db\Company::STATUS_APPROVED), \Tk\Db\Tool::create('RAND()'))->current();
                $adapter = new \Coa\Adapter\Supervisor($this->coa, $supervisor);
                break;
            case 'company':
                $company = \App\Db\CompanyMap::create()->findFiltered(
                    array('courseId' => $this->coa->courseId, 'status' => \App\Db\Company::STATUS_APPROVED), \Tk\Db\Tool::create('RAND()'))->current();
                $adapter = new \Coa\Adapter\Company($this->coa, $company);
                break;
            case 'staff':
                $staff = \App\Db\UserMap::create()->findFiltered(
                    array('courseId' => $this->coa->courseId, 'type' => \Uni\Db\User::TYPE_STAFF), \Tk\Db\Tool::create('RAND()'))->current();
                $adapter = new \Coa\Adapter\User($this->coa, $staff);
                break;
            case 'student':
                $student = \App\Db\UserMap::create()->findFiltered(
                    array('subjectId' => $this->getConfig()->getSubjectId(), 'type' => \Uni\Db\User::TYPE_STUDENT), \Tk\Db\Tool::create('RAND()'))->current();
                $adapter = new \Coa\Adapter\User($this->coa, $student);
                break;
        }
        if (!$adapter) {
            //return '<h2>Cannot find an Adapter object for this COA Type: ' . $this->coa->type . '</h2>';
            throw new \Tk\Exception('Cannot find an Adapter object for this COA Type: ' . $this->coa->getType() );
        }
        $value = array(
            'dateStart' => $this->getConfig()->getSubject()->getDateStart(\Tk\Date::FORMAT_SHORT_DATE),
            'dateEnd' => $this->getConfig()->getSubject()->getDateEnd(\Tk\Date::FORMAT_SHORT_DATE)
        );
        $adapter->replace($value);

        $ren = \Coa\Ui\Pdf\Certificate::create($adapter, 'Sample');
        $ren->output();     // comment this to see html version
        return $ren->show();
    }

    public function initActionPanel()
    {
        if ($this->coa->getId()) {
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Preview',
                \Uni\Uri::create()->set('preview')->noCrumb(), 'fa fa-eye'))->setAttr('target', '_blank');
            $this->getActionPanel()->append(\Tk\Ui\Link::createBtn('Send To',
                \Uni\Uri::createSubjectUrl($this->getRecipientSelectUrl($this->coa->getType()))
                    ->set('coaId', $this->coa->getId()), 'fa fa-envelope-o'));
        }
    }

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $this->initActionPanel();
        $template = parent::show();
        
        // Render the form
        $template->appendTemplate('form', $this->form->show());

        return $template;
    }

    /**
     * @param $type
     * @return string
     */
    private function getRecipientSelectUrl($type)
    {
        $url = '/coaSupervisor.html';
        switch ($type) {
            case \Coa\Db\Coa::TYPE_SUPERVISOR:
                $url = '/coaSupervisor.html';
                break;
            case \Coa\Db\Coa::TYPE_COMPANY:
                $url = '/coaCompany.html';
                break;
            case \Coa\Db\Coa::TYPE_STAFF:
                $url = '/coaStaff.html';
                break;
            case \Coa\Db\Coa::TYPE_STUDENT:
                $url = '/coaStudent.html';
                break;
        }
        return $url;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div class="tk-panel" data-panel-icon="fa fa-certificate" var="form"></div>
HTML;
        return \Dom\Loader::load($xhtml);
    }

}