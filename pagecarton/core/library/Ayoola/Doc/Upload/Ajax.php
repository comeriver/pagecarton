<?php
/**
 * PageCarton
 *
 * LICENSE
 *
 * @category   PageCarton
 * @package    Ayoola_Doc_Upload_Ajax
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Ajax.php 4.17.2012 7.55am ayoola $
 */

/**
 * @see Ayoola_Doc_Upload_Exception 
 */
 
require_once 'Ayoola/Doc/Exception.php';


/**
 * @category   PageCarton
 * @package    Ayoola_Doc_Upload_Ajax
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

class Ayoola_Doc_Upload_Ajax extends Ayoola_Doc_Upload_Abstract
{
	
    /**
     * Whether class is playable or not
     *
     * @var boolean
     */
	protected static $_playable = true;
	
    /**
     * Access level for player
     *
     * @var boolean
     */
	protected static $_accessLevel = 0;
		
    /**
     * Does the class process
     * 
     */
	public function init()
    {
		try
		{
			$this->_objectData['status'][] = 'received.';
		//	var_export( $_FILES );
		//	var_export( $_POST );
			$docSettings = Ayoola_Doc_Settings::getSettings( 'Documents' );
			//		var_export( $docSettings );

		//	$this->_objectData['status'][] = $docSettings;
			if( ! @$_POST['image'] && ! @$_POST['document'] ) 
			{
				if( @$_FILES['upload']['tmp_name'] ) 
				{
					$tempFilename = tempnam( CACHE_DIR, '/upload/' ); 
					move_uploaded_file( $_FILES['upload']['tmp_name'], $tempFilename );
					$_POST['name'] = $_FILES['upload']['name'];
					$_POST['mime_type'] = $_FILES['upload']['type'];
				//	$_POST['document'] = 'data:' . $_POST['mime_type'] . ';base64,' . base64_encode( file_get_contents( $tempFilename ) );
					$_POST['document'] = base64_encode( file_get_contents( $tempFilename ) );
				}
				else
				{
					//	debug some machines don't populate post
					if( $response = file_get_contents( "php://input") )
					{
				//		var_export( http_response_code() );
				//		http_response_code( 200 );
				//		var_export( http_response_code() );
						header('HTTP/1.1 200 Found');
						
						parse_str( $response, $result );
						if( isset( $result['image'] ) || isset( $result['document'] ) )
						{
							$_POST = $result;
						}
					}
					else
					{
						return false;
					}

				}
			}
			@$_POST['name'] = $_POST['name'] ? : '_file_';
//			if( ! Ayoola_Abstract_Table::hasPriviledge( ) )
			$filenameToUse = null;
			@$docSettings['allowed_uploaders'] = @$docSettings['allowed_uploaders'] ? : array();
			@$docSettings['allowed_uploaders'][] = 98;	// allow us to user domain owners
			if( ! Ayoola_Abstract_Table::hasPriviledge( @$docSettings['allowed_uploaders'] ) )
			{ 
				//	We are not authorized to upload document, can we upload a profile picture?
				if( @in_array( 'allow_profile_pictures', $docSettings['options'] ) && Ayoola_Application::getUserInfo( 'username' ) && Ayoola_Access::getAccessInformation( Ayoola_Application::getUserInfo( 'username' ) ) )
				{
					switch( $_POST['mime_type'] )
					{
						case 'image/png':
						case 'image/jpg':
						case 'image/jpeg':
						case 'image/gif':
							//	Allowed if we are uploading image
							//	format extension
							$extension = strtolower( array_pop( explode( '.', $_POST['name'] ) ) );
							switch( $extension )
							{
								case 'png':
								case 'jpg':
								case 'jpeg':
								case 'gif':
									//	Allowed if we are uploading image									
								break;
								default:
									return false;
								break;
							}
						break;
						default:
							return false;
						break;
					}
					$filenameToUse = Ayoola_Application::getUserInfo( 'username' ) . '';
					$this->_objectData['status'][] = 'Profile Picture';
				}
				else
				{
					//	if its new install, lets allow for specific files
					

					$message = 'You are not allowed to upload a file.';
					$this->_objectData['badnews'][2] = $message;
					$this->_objectData['error'] = @array_pop( $this->_objectData['badnews'] );
					$this->_objectData['status'][] = 'failed';
					$this->setViewContent( $message );
					return false;
				}
			}
			$this->_objectData['status'][] = 'authorized';
			$dir = Ayoola_Doc::getDocumentsDirectory(); 
			
			if( ! empty( $_POST['suggested_url'] ) && self::hasPriviledge( array( 99, 98 ) ) )
			{
				$extension = strtolower( array_pop( explode( '.', trim( $_POST['suggested_url'], '.' ) ) ) );
				if( $extension == 'php' )
				{
					throw new Ayoola_Doc_Upload_Exception( 'YOU CANNOT SUGGEST A PHP URL' );
				}
				if( ! in_array( strlen( $extension ), range( 1, 4 ) ) )
				{
					throw new Ayoola_Doc_Upload_Exception( 'INVALID EXTENSION ' . $extension );
				}
			
				//	We cant upload to /ayoola/ 
			//	var_export( strtolower( array_pop( explode( '.', trim( $_POST['suggested_url'], '.' ) ) ) ) );
				if( array_shift( explode( '/', trim( $_POST['suggested_url'], '/' ) ) ) == 'ayoola' )
				{
					throw new Ayoola_Doc_Upload_Exception( 'UPLOADING IN /ayoola/ NOT ALLOWED' );
				}
				
				//	We now have the chance to suggest URL if we are admin
			//	$url = $_POST['suggested_url'];
				$url = '';
				$dir .= $url;
				$path = $dir . $_POST['suggested_url'];
				$url = $url . $_POST['suggested_url'];
				Ayoola_Doc::createDirectory( dirname( $path ) );
				
			}
			else
			{
				$url = '/';
				
				$url .= 'public/';
				
				//	format extension
				$extension = explode( '.', $_POST['name'] );
				$extension = strtolower( array_pop( $extension ) );
				
				if( @in_array( 'private_directory', $docSettings['options'] ) && Ayoola_Application::getUserInfo( 'username' ) ) 
				{
					$personalDir = implode( DS, str_split( strval( Ayoola_Application::getUserInfo( 'user_id' ) ) ) );
					$url .= $personalDir . '/';
				}
				if( $filenameToUse )
				{
					$dir .= $url;
					Ayoola_Doc::createDirectory( $dir );
					$url = $url . $filenameToUse . '.' . $extension;
					$path = $dir . $filenameToUse . '.' . $extension;
				} 
				else
				{  
					//	We may set the path in the request
					$validator = new Ayoola_Validator_Uri();
					if( @isset( $_POST['directory'] ) && $validator->validate( rtrim( $_POST['directory'], '/' ) ) )
					{
						$url .= trim( $_POST['directory'], ' /' ) . '/';
					}
					$url .= date( 'Y/m/d/' );
					$filter = new Ayoola_Filter_Name(); 
					$filter->replace = '_'; 
					
				//	var_export( strtolower( array_pop( explode( '.', trim( $_POST['suggested_url'], '.' ) ) ) ) );
					if( ! in_array( strlen( $extension ), range( 1, 4 ) ) )
					{
						throw new Ayoola_Doc_Upload_Exception( 'INVALID EXTENSION ' . $extension );
					}
				//	$_POST['name'] = array_shift( explode( '.', $_POST['name'] ) );
					$_POST['name'] = substr( $_POST['name'], 0, 30 );
					$_POST['name'] = $_POST['name'] ? : uniqid();
					$_POST['name'] = str_replace( '.', '_', $_POST['name'] );
					$newName = $filter->filter( $_POST['name'] );
					$dir .= $url;
					Ayoola_Doc::createDirectory( $dir );
					
					//	We won't overite files
					$i = 0;
					do
					{
						//	Avoid this on the first try.
						if( $i )
						{
							$newName = $filter->filter( $_POST['name'] ) . '_' . ++$i; 
						}
						else
						{
							++$i;
							//	Avoid an infinite loop on a first duplicate try.
						}
						$filename = $newName . '.' . $extension;  
						$path = $dir . $filename;
					//	var_export( $filename );
						
					}
					while( is_file( $path ) );
					$url = $url . $filename;
				}
			}
	//		define( 'UPLOAD_DIR', 'D:/Desktop/Graphics/uploads/' );
	
			//	uploading only image for now
			@$img = $_POST['image'] ? : $_POST['document'];
			@$_POST['mime_type'] = $_POST['mime_type'] ? : 'image/jpeg';
			
			//	if mimename is sent, clear it
			$img = str_replace( 'data:' . $_POST['mime_type'] . ';base64,', '', $img );
			
			//	For empty mime name
			$img = str_replace( 'data:;base64,', '', $img );
			$img = str_replace( ' ', '+', $img );
			
		//	var_export( $img );
			$data = base64_decode( $img );
			$this->_objectData['file_info']['path'] = $path;
			$this->_objectData['file_info']['url'] = $url;
			$this->_objectData['file_info']['dedicated_url'] = $url;
			$this->_objectData['file_info']['domain'] = Ayoola_Page::getDefaultDomain();
			$urlPrefix = Ayoola_Application::getUrlPrefix();
		
			
			//	refresh cache
			if( $dedicatedUri = Ayoola_Doc::uriToDedicatedUrl( $url, array( 'disable_cache' => true ) ) )  
			{
				$this->_objectData['file_info']['dedicated_url'] = $dedicatedUri;
				$this->_objectData['file_info']['dedicated'] = $dedicatedUri;
			}  

			$this->_objectData['uploaded'] = 1;
			$this->_objectData['url'] = $urlPrefix . $this->_objectData['file_info']['dedicated_url'];
			$this->_objectData['fileName'] = $_POST['name'];
			$this->_objectData['error'] = @array_pop( $this->_objectData['badnews'] );
			$this->_playMode = static::PLAY_MODE_JSON;			
			if( isset( $_GET['CKEditorFuncNum'] ) )
			{
				$this->_playMode = static::PLAY_MODE_HTML;			
				// Required: anonymous function reference number as explained above.
				$funcNum = $_GET['CKEditorFuncNum'] ;
				// Optional: instance name (might be used to load a specific configuration file or anything else).
				$CKEditor = $_GET['CKEditor'] ;
				// Optional: might be used to provide localized messages.
				$langCode = $_GET['langCode'] ;
				// Optional: compare it with the value of `ckCsrfToken` sent in a cookie to protect your server side uploader against CSRF.
				// Available since CKEditor 4.5.6.
		//		$token = $_POST['ckCsrfToken'] ;

				// Check the $_FILES array and save the file. Assign the correct path to a variable ($url).
				$url = $this->_objectData['file_info']['dedicated_url'];
				// Usually you will only assign something here if the file could not be uploaded.
				$message = 'File was successfuly uploaded.';
				$html = "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '{$urlPrefix}{$url}', '$message');</script>";		
				$this->setViewContent( $html, true );				
			}
			
		//	var_export( $_POST['name'] );
			$success = file_put_contents( $path, $data );
			
			//	refresh cache again after successful upload.
			if( $dedicatedUri = Ayoola_Doc::uriToDedicatedUrl( $url, array( 'disable_cache' => true ) ) )  
			{
				$this->_objectData['file_info']['dedicated_url'] = $dedicatedUri;
				$this->_objectData['file_info']['dedicated'] = $dedicatedUri;
			}  
			
			//	Server-side resize
			// include ImageManipulator class
		//	require_once('ImageManipulator.php');
		 
		//	foreach ($_FILES as $file) 
			do
			{
				// array of valid extensions
				$validExtensions = array( '.jpg', '.jpeg', '.png' );   
				
				// get extension of the uploaded file
				$fileExtension = strtolower( strrchr( $path, ".") );
				
				// check if file Extension is on the list of allowed ones
				if (in_array($fileExtension, $validExtensions)) 
				{
					$newNamePrefix = time() . '_';
					$manipulator = new ImageManipulator( $path );
					$width  = $manipulator->getWidth();
					$height = $manipulator->getHeight();
					$centreX = round( $width / 2 );
					$centreY = round( $height / 2 );
					
					//	Setting the default to my screensize
					$maxWith = @intval( $_POST['max_width'] ) ? : 3000;
					$maxHeight = @intval( $_POST['max_height'] ) ? : 3000; 
					
			//		var_export( $width );
			//		var_export( $maxWith );
			//		var_export( $height );
			//		var_export( $maxHeight );  
			//		var_export( $_REQUEST['crop'] );
					
					if( $width != $maxWith || $height != $maxHeight )
					{
		//			var_export( $_REQUEST['crop'] );  
						if( ! empty( $_REQUEST['crop'] ) )
						{
							ImageManipulator::makeThumbnail( $path, $maxWith, $maxHeight, $path );
						}
						//	No need for manipulation
						break;
					}
					
			//		var_export( __CLASS__ );
					
					// our dimensions will be 200x130
					$x1 = $centreX - ( $maxWith / 2 ); 
					$y1 = $centreY - ( $maxHeight / 2 ); 
			 
					$x2 = $centreX + ( $maxWith / 2 ); 
					$y2 = $centreY + ( $maxHeight / 2 ); 
			 
					// center cropping to 200x130
					//	This does the actual cropping
					//	 expand small pictures
					//	not doing anything the top one isnt doing
				//	$newImage = $manipulator->crop($x1, $y1, $x2, $y2);
					// saving file to uploads folder
				//	$manipulator->save( $path );
			
					//	refresh cache after resize
					if( $dedicatedUri = Ayoola_Doc::uriToDedicatedUrl( $url, array( 'disable_cache' => true ) ) )  
					{
						$this->_objectData['file_info']['dedicated_url'] = $dedicatedUri;
						$this->_objectData['file_info']['dedicated'] = $dedicatedUri;
					}  
				//	echo 'Done ...';
				} else {
				//	echo 'You must upload an image...';
				}
			}	
			while( false );

			//	Put this in the Documents DB Table 
			$table = Ayoola_Doc_Table::getInstance();
			$table->insert( array(
						'url' => $url,
						'upload_time' => time(),
						'username' => Ayoola_Application::getUserInfo( 'username' ),
				//		'access_level' => time(),
			) );

			$message = 'File successfully uploaded.';
			$this->_objectData['goodnews'][] = $message;
			$this->_objectData['status'][] = 'done';
			$this->setViewContent( $message );
			return true;
		}
		catch( Application_Slideshow_Exception $e )
		{ 
			return false; 
		}
	}
	
	// END OF CLASS
}
