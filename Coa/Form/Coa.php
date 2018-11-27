<?php
namespace Coa\Form;

use Tk\Form\Field;
use Tk\Form\Event;
use Tk\Form;

/**
 * Example:
 * <code>
 *   $form = new Coa::create();
 *   $form->setModel($obj);
 *   $formTemplate = $form->getRenderer()->show();
 *   $template->appendTemplate('form', $formTemplate);
 * </code>
 * 
 * @author Mick Mifsud
 * @created 2018-11-26
 * @link http://tropotek.com.au/
 * @license Copyright 2018 Tropotek
 */
class Coa extends \Bs\FormIface
{

    /**
     * @throws \Exception
     */
    public function init()
    {

        $list = array('Company' => 'company', 'Staff' => 'staff', 'Student' => 'student');
        $this->appendField(new Field\Select('type', $list))->prependOption('-- Select --', '');
        //$this->appendField(new Field\Input('type'));
        $this->appendField(new Field\Input('subject'));

        $this->form->addField(new Field\File('background', $this->getCoa()->getDataPath()))
            ->setMaxFileSize($this->getConfig()->get('upload.profile.imagesize'))->setAttr('accept', '.png,.jpg,.jpeg,.gif')
            ->addCss('tk-imageinput')
            ->setNotes('Upload the background image for the certificate (Recommended Size: 1300x850)');

        $htmlEl = $this->appendField(new Field\Textarea('html'))
            ->addCss('mce')->setAttr('data-elfinder-path', $this->getCoa()->getProfile()->getInstitution()->getDataPath().'/media');
        if ($this->getCoa()->getBackgroundUrl()) {
            $htmlEl->setAttr('data-background-image', $this->getCoa()->getBackgroundUrl());
        }
        $this->appendField(new Field\Textarea('emailHtml'))
            ->addCss('mce-med')->setAttr('data-elfinder-path', $this->getCoa()->getProfile()->getInstitution()->getDataPath().'/media');

        $this->appendField(new Event\Submit('update', array($this, 'doSubmit')));
        $this->appendField(new Event\Submit('save', array($this, 'doSubmit')));
        $this->appendField(new Event\Link('cancel', $this->getBackUrl()));



        $js = <<<JS
jQuery(function ($) {
  
  var ed = $('#coa-html').tinymce();
  ed.on('init', function (e) {
    //console.log($(this.targetElm).data('backgroundImage'));
    //console.log(this.dom.getRoot());
    var body = this.dom.getRoot(); 
    ed.dom.setStyle(body, 'background-image', "url('"+$(this.targetElm).data('backgroundImage')+"')");
    ed.dom.setStyle(body, 'background-repeat', "no-repeat");
    ed.dom.setStyle(body, 'background-size', "1300px 850px");
    ed.dom.setStyle(body, 'width', "1300px");
    ed.dom.setStyle(body, 'height', "850px");
    ed.dom.setStyle(body, 'background-color', "#EFEFEF");
    
  });
  
});
JS;
        $this->getRenderer()->getTemplate()->appendJs($js);



    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function execute($request = null)
    {
        $this->load(\Coa\Db\CoaMap::create()->unmapForm($this->getCoa()));
        parent::execute($request);
    }

    /**
     * @param Form $form
     * @param Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        // Load the object with form data
        \Coa\Db\CoaMap::create()->mapForm($form->getValues(), $this->getCoa());

        /** @var \Tk\Form\Field\File $image */
        $image = $form->getField('background');

        // Do Custom Validations
        if ($image->hasFile() && !preg_match('/\.(gif|jpe?g|png)$/i', $image->getValue())) {
            $form->addFieldError('background', 'Please Select a valid image file. (jpg, png, gif)');
        }

        $form->addFieldErrors($this->getCoa()->validate());
        if ($form->hasErrors()) {
            return;
        }
        
        $isNew = (bool)$this->getCoa()->getId();
        $this->getCoa()->save();

        $image->saveFile();


        \Tk\Alert::addSuccess('Record saved!');
        $event->setRedirect($this->getBackUrl());
        if ($form->getTriggeredEvent()->getName() == 'save') {
            $event->setRedirect(\Tk\Uri::create()->set('coaId', $this->getCoa()->getId()));
        }
    }

    /**
     * @return \Tk\Db\ModelInterface|\Coa\Db\Coa
     */
    public function getCoa()
    {
        return $this->getModel();
    }

    /**
     * @param \Coa\Db\Coa $coa
     * @return $this
     */
    public function setCoa($coa)
    {
        return $this->setModel($coa);
    }
    
}