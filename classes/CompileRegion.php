<?php
/**
 * Created by PhpStorm.
 * User: germain
 * Date: 06/12/2018
 * Time: 17:04
 */

class CompileRegion
{


    /**
     * @var $sourceText string
     */
    private $sourceText;

    private $source;






    private $out;


    public function __construct()
    {
        $this->out = array();
    }


    /**
     * @param $path
     */

    public function fromJSONFile($oJsonStat){

        //if(file_exists($path)){
            //$this->sourceText = file_get_contents($path);
            $this->fromObject(json_decode($oJsonStat));
        //} else {
            //throw new ErrorException("Le fichier n'existe pas");
        //}

    }

    public function fromObject($source){

        $this->source = $source;


        //var_dump($this);

        $this->parse();

    }




    public function toObject(){
        return $this->out;
    }


    public function toJSON(){
        return json_encode($this->toObject());
    }


    private function parse(){



        foreach ($this->source as $line){

            $currentRegion = null;
            //Test de Region
            $regIndex = $this->regionIndex($line->region);

            //Pas trouvé
            if($regIndex == -1){
                $currentRegion = $this->getNewRegion($line->region);
                $this->out[] = $currentRegion;
            } else {
                $currentRegion = $this->out[$regIndex];

            }

            $currentDep = null;
            $depIndex = $this->depIndex($line->dep, $currentRegion);

            //Pas trouvé
            if($depIndex == -1){
                $currentDep = $this->getNewDep($line->dep);
                $currentRegion->children[] = $currentDep;
            } else {


                $currentDep = $currentRegion->children[$depIndex];
                if ($line->etablissementsiege) {
                    $currentDep->etablissementsiege++;
                }
            }

            $currentDep->nbouverure +=$line->nbouverure;

        }

    }

    private function regionIndex($regionName){



        for ($i = 0; $i < count($this->out); $i++){


            //var_dump($regionName, $this->out[$i]->name);
            if(strtolower($this->out[$i]->name) == strtolower($regionName)){
                return $i;
            }
        }

        return -1;
    }

    private function depIndex($depName, $pRegion){

        for ($i = 0; $i < count($pRegion->children); $i++){
            if(strtolower($pRegion->children[$i]->name) == strtolower($depName)){
                return $i;
            }
        }

        return -1;
    }


    private function getNewRegion($sName = ""){
        $reg = new stdClass();
        $reg->name = $sName;
        $reg->children = array();
        return $reg;
    }


    private function getNewDep($sName = ""){
        $reg = new stdClass();
        $reg->name = $sName;
        $reg->nbouverure = 0;
        $reg->etablissementsiege = 0;

        return $reg;
    }

    private function getNewCountry(){
        $reg = new stdClass();
        $reg->name = "France";
        $reg->children = array();

        return $reg;
    }


}