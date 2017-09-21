<?php

class Shopware_Plugins_Frontend_Boxalino_Helper_BxFiles{


    /**
     * @var string
     */
    public $XML_DELIMITER = ',';

    /**
     * @var string
     */
    public $XML_ENCLOSURE = '"';

    protected $_mainDir;
    protected $account;
    protected $_dir;
    protected $type;
    public function __construct($dirPath, $account, $type)
    {
        $this->_mainDir = $dirPath;
        $this->account = $account;
        $this->type = $type;
        $this->init();
    }

    private function init(){

        $this->_dir = $this->_mainDir . $this->account . '_' . $this->type . '_' . microtime(true);
        if (!file_exists($this->_dir)) {
            mkdir($this->_dir, 0777, true);
        }else{
            $this->delTree($this->_dir);
            mkdir($this->_dir, 0777, true);
        }
    }

    private function delTree($dir){

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delTree("$dir/$file");
            } else if (file_exists("$dir/$file")) {
                @unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }

    public function savePartToCsv($file, &$data){
        $path = $this->getPath($file);

        $fh = fopen($path, 'a');

        foreach($data as $dataRow){
            fputcsv($fh, $dataRow, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
        }

        fclose($fh);
        $data = null;
    }

    public function getFileContents($file){
        return file_get_contents($this->getPath($file));
    }

    public function getPath($file) {

        //save
        if (!in_array($file, $this->_files)) {
            $this->_files[] = $file;
        }

        return $this->_dir . '/' . $file;
    }

}