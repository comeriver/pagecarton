<?php

/**
 * PageCarton Content Management System
 *
 * LICENSE
 *
 * @category   PageCarton CMS
 * @package    Ayoola_Page_Layout_ReplaceText
 * @copyright  Copyright (c) 2018 PageCarton (http://www.pagecarton.org)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: ReplaceText.php Thursday 27th of September 2018 11:57PM ayoola@ayoo.la $
 */

/**
 * @see PageCarton_Widget
 */

class Ayoola_Page_Layout_ReplaceText extends Ayoola_Page_Layout_Abstract
{
	
    /**
     * Access level for player. Defaults to everyone
     *
     * @var boolean
     */
	protected static $_accessLevel = array( 98 );
	
    /**
     * 
     * 
     * @var string 
     */
	protected static $_objectTitle = 'Update Text';     

    /**
     * Performs the whole widget running process
     * 
     */
	public static function getUpdates()
    { 
        $settingsName = __CLASS__;
        $table = Application_Settings::getInstance();
        $themeName =  ( ( @$_REQUEST['pc_page_editor_layout_name'] ? : @$_REQUEST['pc_page_layout_name'] ) ? : @$_REQUEST['layout_name'] ) ? : Ayoola_Page_Editor_Layout::getDefaultLayout();
    //    var_export( $themeName );
        $themeInfo = Ayoola_Page_PageLayout::getInstance()->selectOne( null, array( 'layout_name' => $themeName ) );
    //    var_export( $themeInfo );
        if( $previousData = $table->selectOne( null, array( 'settingsname_name' => $settingsName ) ) )
        {
    //      var_export( $previousData );
            $previousData = unserialize( $previousData['settings'] );
     //    var_export( $themeInfo );
           if( is_array( $previousData['dummy_title'] ) && is_array( $previousData['dummy_search'] ) && is_array( $previousData['dummy_replace'] ) )
            {
                $themeInfo['dummy_title'] = array_merge( $themeInfo['dummy_title'], $previousData['dummy_title'] );
                $themeInfo['dummy_search'] = array_merge( $themeInfo['dummy_search'], $previousData['dummy_search'] );
                $themeInfo['dummy_replace'] = array_merge( $themeInfo['dummy_replace'], $previousData['dummy_replace'] );
            }
        }    
        
    //    var_export( $themeInfo );
    //    var_export( $previousData );
    return $themeInfo;   
    }

    /**
     * Performs the whole widget running process
     * 
     */
	public function init()
    {    
		try
		{ 
            //  Code that runs the widget goes here...
            try
            { 
                $this->setIdentifier();
            }
            catch( Exception $e )
            { 
                $this->_identifier[$this->getIdColumn()] = Ayoola_Page_Editor_Layout::getDefaultLayout();
            //	return false; 
            }
            if( ! $identifierData = self::getIdentifierData() ){ return false; }
            $settingsName = __CLASS__;

            $this->createForm( 'Continue..', '' );
			$this->setViewContent( '<div class="pc-notify-info" style="text-align:center;">Update text on the site! <a style="font-size:smaller;" onclick="location.search+=\'&editing_dummy_text=1\'" href="javascript:">Advanced mode</a></div>' );
			$this->setViewContent( $this->getForm()->view() );
		//	self::v( $_POST );
            if( ! $values = $this->getForm()->getValues() ){ return false; }
         //   self::v( $identifierData );
      //      self::v( $values );
    //        $identifierData += $values;
            foreach( $values['dummy_replace'] as $key => $each )
            {
                if( '' === $each )
                {
                    $values['dummy_replace'][$key] = trim( $values['dummy_search'][$key], '{}' );
                }

            }
            $this->updateDb( $values );
            $previousData = Ayoola_Page_Layout_ReplaceText::getUpdates();
            $table = Application_Settings::getInstance();
        //    var_export( $previousData );
            if( $previousData )
            {
                $table->delete( array( 'settingsname_name' => $settingsName ) );
                $values['dummy_title'] = array_merge( $previousData['dummy_title'], $values['dummy_title'] );
                $values['dummy_search'] = array_merge( $previousData['dummy_search'], $values['dummy_search'] );
                $values['dummy_replace'] = array_merge( $previousData['dummy_replace'], $values['dummy_replace'] );
            }
        //    var_export( $values );
			$table->insert( array( 'settings' => serialize( $values ), 'settingsname_name' => $settingsName ) );

            
			$this->setViewContent( '<div class="goodnews" style="xtext-align:center;">Update saved successfully. Further text update could be done in <a href="/tools/classplayer/get/name/Ayoola_Page_List">Pages</a>. </div>', true );
            // end of widget process
          
		}  
		catch( Exception $e )
        { 
            //  Alert! Clear the all other content and display whats below.
        //    $this->setViewContent( '<p class="badnews">' . $e->getMessage() . '</p>' ); 
            $this->setViewContent( '<p class="badnews">Theres an error in the code</p>' ); 
            return false; 
        }
	}
	
    /**
     * creates the form for creating and editing page
     * 
     * param string The Value of the Submit Button
     * param string Value of the Legend
     * param array Default Values
     */
	public function createForm( $submitValue = null, $legend = null, Array $values = null )  
    {
		//	
        $form = new Ayoola_Form( array( 'name' => $this->getObjectName() . $values['page_id'] . $values['url'], 'data-not-playable' => true ) );
		$form->submitValue = $submitValue ;
	//	$form->oneFieldSetAtATimeJs = true;

    //    if( ! $data = self::getIdentifierData() ){ return false; }
        $data = Ayoola_Page_Layout_ReplaceText::getUpdates();
        
    //    var_export( $data );

        $i = 0;
        do
        {
            $fieldset = new Ayoola_Form_Element;
            if( empty( $data['dummy_title'][$i] ) || ! empty( $_REQUEST['editing_dummy_text'] ) )
            {
                $fieldset->addElement( array( 'name' => 'dummy_title', 'multiple' => 'multiple', 'label' => 'Title', 'placeholder' => 'Name for dummy text', 'type' => 'InputText', 'value' => @$data['dummy_title'][$i] ? : $data['dummy_search'][$i] ) );
                $fieldset->allowDuplication = true;
                $fieldset->duplicationData = array( 'add' => '+ Add New Text Below', 'remove' => '- Remove Above Text', 'counter' => 'subgroup_counter', );
                $fieldset->container = 'span';
                $fieldset->placeholderInPlaceOfLabel = false;
            }
            if( empty( $data['dummy_search'][$i] ) || ! empty( $_REQUEST['editing_dummy_text'] ) )
            {
                $fieldset->addElement( array( 'name' => 'dummy_search', 'multiple' => 'multiple', 'label' => 'Dummy Text', 'placeholder' => @$data['dummy_search'][$i], 'type' => 'TextArea', 'value' => @$data['dummy_search'][$i] ) );
            }
            $info = array( 'name' => 'dummy_replace', 'multiple' => 'multiple', 'label' => $data['dummy_title'][$i] ? : ' ', 'placeholder' => @$data['dummy_search'][$i], 'type' => 'TextArea', 'value' => ( @$data['dummy_replace'][$i] || ! empty( $_REQUEST['editing_dummy_text'] ) ) ? $data['dummy_replace'][$i] : trim( @$data['dummy_search'][$i], '{}' ) );
            if( strip_tags( $data['dummy_search'][$i] ) !== $data['dummy_search'][$i] )
            {
                $info['data-html'] = '1';
            //    var_export( $info );
            }
            if( ! empty( $_REQUEST['editing_dummy_text'] ) )
            {
                $info['label'] = 'Default Replacement';
                $info['label'] = 'Default Replacement';
            //    var_export( $info );
            }
            $fieldset->addElement( $info );
            $form->addFieldset( $fieldset );
            ++$i;
        }
        while(  isset( $data['dummy_search'][$i] )  );
        Application_Article_Abstract::initHTMLEditor();
    
		$this->setForm( $form );
    } 
	// END OF CLASS
}
