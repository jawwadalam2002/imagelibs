<?php
require './src/claviska/SimpleImage.php';
define("IMAGE_PATH","images/"); // image folder path
define("IMAGE_SIZE_ALLOWED",10485760); // File size in bytes
$imageMimeTypeAllowed=array("image/gif","image/jpeg","image/png"); // Allowed extentions
//$imageSizeAllowed=10485760; // File size in bytes
// Ignore notices
error_reporting(E_ALL & ~E_NOTICE);


/** rrmdir Remove Directory and its items 
  * @$dir Direcotry Path
  * @Return null
*/
function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
} 

/**
  * setSaveImage save posted image
  * @$imageName Image Name will be use to create directory and set image name
  * @Return Array 
*/
function setSaveImage($imageName){
	if(isset($_FILES['image'])){
		$errors= array();
		$fileSize =$_FILES['image']['size'];
		$fileTemp =$_FILES['image']['tmp_name'];
		$file_type=$_FILES['image']['type'];
		$fileExtention=strtolower(end(explode('.',$_FILES['image']['name'])));
		$expensions= array("jpg","jpeg","png","gif");
		
		if(in_array($fileExtention,$expensions)=== false){
			$errors[]="extension not allowed.";
		}
		
		if($fileSize > IMAGE_SIZE_ALLOWED){
			$errors[]='File size must not greater then 10MB';
		}
		
		if(empty($errors)){
			mkdir(IMAGE_PATH.$imageName);
			move_uploaded_file($fileTemp,IMAGE_PATH.$imageName."/".$imageName.".".$fileExtention);
			return array(
						 "success"=>true,
						 "data"=>array(
						 			"filePath"=>IMAGE_PATH.$imageName."/".$imageName.".".$fileExtention,
						 			"fileExtention"=>$fileExtention
						 		)
						 );
		}else{
			return array("success"=>false, "data"=>$errors);
		} 
	}
	return array("success"=>false, "data"=>array("File Not Found"));
}

/**
  * curlUrl use to save image from external URI
  * @$url url to the image
  * @$saveto complete path with folder and image name with extention to save image
  * @Return Array
*/
function curlUrl($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
	
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
	return array(
				 "success"=>true,
				 "data"=>array(
							"filePath"=>$saveto
						)
				 );
}

if(isset($_FILES['image'])|| $_POST["url"]){
	$uniqueFileName=time();
	if(empty($_POST["url"])) // If URL is empty it will call setSaveImage function to save image
		$arrGetImageInfo=setSaveImage($uniqueFileName);
	else{
		mkdir(IMAGE_PATH.$uniqueFileName."/"); //If URL is not empty it will call curlUrl to save image from external link
		$arrGetImageInfo=curlUrl($_POST["url"],IMAGE_PATH.$uniqueFileName."/".$uniqueFileName.".jpg");
	}
	
	// $arrGetImageInfo["success"] == false then it will show error
	if(!$arrGetImageInfo["success"]){
		echo "<ul>";
		foreach($arrGetImageInfo["data"] as $rawImageInfo)
			echo "<li>".$rawImageInfo."</li>";
		echo "</ul>";
		exit;	
	}
	
	
	try{
		$image = new \claviska\SimpleImage();
		$image->fromFile($arrGetImageInfo["data"]["filePath"]);
		
		// Check if image type is allowed
		if(!in_array($image->getMimeType(),$imageMimeTypeAllowed)){
			echo "extension not allowed.";
			rmdir(IMAGE_PATH.$uniqueFileName."/");
			exit;
		}
		
		$intImageWidth=$image->getWidth(); // width ratio of image
		
		// check if image width is greater then 300 then it will convert that image otherwise it will not convert
		if($intImageWidth>300){
			$image->fitToWidth(300); // set Image width for 300px
			$image->toFile($arrGetImageInfo["data"]["filePath"]); // save image to a new folder
			$image->fitToWidth(80); // set icon image PNG
			$image->toFile(IMAGE_PATH.$uniqueFileName."/icon-".$uniqueFileName.".png","image/png"); //save icon image to a new folder
		}
		$image->toScreen("image/png"); // show PNG image after save image
	}catch(Exception $ex){
		echo "File is not correct";
		rrmdir(IMAGE_PATH.$uniqueFileName."/");
		exit;
	}
}
?>




<form action="#" method="post" enctype="multipart/form-data">
    <table align="center" width="100">
        <tr>
            <td>Image Url</td>
            <td><input type="text" name="url" /></td>
        </tr>
        
        <tr>
            <td>Browse Image</td>
            <td><input type="file" name="image" /></td>
        </tr>
        
        <tr>
            <td><input type="submit" value="upload image" /></td>
            <td><input type="reset" value="Reset" /></td>
        </tr>
    </table>
</form>
