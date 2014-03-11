<?php

class Ecube_Jcubelink_Adminhtml_JcubelinkController extends Mage_Adminhtml_Controller_Action {
    protected $_helper;

    protected function helper() {
        if (!isset($this->_helper))
            $this->_helper = Mage::helper('jcubelink');
        return $this->_helper;
    }

    protected function _initAction() {
        $this->loadLayout();
        $this->_setActiveMenu('jcubelink');
        return $this;
    }

    public function indexAction() {
        $this->_initAction()->renderLayout();
    }

    public function downloadLogAction() {
        $logPath = Mage::getBaseDir('var') . DS . 'log' . DS;
        $logFile = $logPath . 'jcubelink.log';
        if (!file_exists($logFile))
            $this->_redirectReferer();

        $zip = new ZipArchive();
        $fileName = $logPath . 'jcubelink_log.zip';
        if ($zip->open($fileName, ZipArchive::OVERWRITE) === true) {
            $zip->addFile($logFile, 'jcubelink.log');
            $zip->close();


            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', 'application/octet-stream', true);

            $this->getResponse()
                ->setHeader('Content-Disposition', 'inline; filename=jcubelink_log.zip');

            $this->getResponse()
                ->clearBody();
            $this->getResponse()
                ->sendHeaders();

            session_write_close();
            echo file_get_contents($fileName);

            unlink($fileName);
        }
    }

    public function clearLogAction() {
        $logPath = Mage::getBaseDir('var') . DS . 'log' . DS;
        $logFile = $logPath . 'jcubelink.log';
        if (file_exists($logFile)) {
            if (unlink($logFile))
                Mage::getSingleton('core/session')->addSuccess('Log cleared');
            else
                Mage::getSingleton('core/session')->addWarning('Could not clear log file, file probably open');
        }
        else
            Mage::getSingleton('core/session')->addNotice('Log file already cleared');
        $this->_redirectReferer();
    }
}
