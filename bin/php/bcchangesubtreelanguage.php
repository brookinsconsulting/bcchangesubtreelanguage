#!/usr/bin/env php
<?php
/**
 * File containing the bcchangesubtreelanguage.php bin script
 *
 * @copyright Copyright (C) 1999 - 2017 Brookins Consulting. All rights reserved.
 * @copyright Copyright (C) 1999 - 2017 Andreas Adelsberger. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.1.1
 * @package bcchangesubtreelanguage
 */

/** Add a starting timing point tracking script execution time **/

$srcStartTime = microtime();

/** Script autoloads initialization **/

require 'autoload.php';

/** Script startup and initialization **/

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "BC Change Subtree Language Script" .
                                                        " sets the main language of the" .
                                                        " child nodes of the provided parent" .
                                                        " node_id and removes all other translations.\n\n" .
                                                        "bcchangesubtreelanguage.php --script-verbose --parent-node-id=42 --content-class-identifier=folder --locale-identifier=eng-GB --test-only" ),
                                     'use-session' => false,
                                     'use-modules' => true,
                                     'use-extensions' => true,
                                     'user' => true ) );

$script->startup();

$options = $script->getOptions( "[script-verbose;][script-verbose-level;][parent-node-id:][content-class-identifier:][locale-identifier;][test-only;]",
                                false,
                                array( 'parent-node-id' => 'Use this parameter to specify the parent node id for a specific content tree subtree of nodes to be modified. Example: ' . "'--parent-node-id=2'" . ' is a required parameter which defaults to false',
                                       'content-class-identifier' => 'Use this parameter to specify the content class identifier string for a specific content class of nodes to be modified. Example: ' . "'--content-class-identifier=folder'" . ' is a required parameter which defaults to false',
                                       'locale-identifier' => 'Use this parameter to specify the content object locale identifier string to assign to the nodes to be modified. Example: ' . "'--locale-identifier=eng-US'" . ' is a required parameter which defaults to eng-GB',
                                       'test-only' => 'Use this parameter to test for objects which need lat/lng attributes to be swapped. Test only no modifications to db made. Example: ' . "'--test-only'" . ' is an optional parameter which defaults to false',
                                       'script-verbose' => 'Use this parameter to display verbose script output without disabling script iteration counting of images created or removed. Example: ' . "'--script-verbose'" . ' is an optional parameter which defaults to false',
                                       'script-verbose-level' => 'Use only with ' . "'--script-verbose'" . ' parameter to see more of execution internals. Example: ' . "'--script-verbose-level=3'" . ' is an optional parameter which defaults to 1 and works till 5'),
                                false,
                                array( 'user' => true ) );
$script->initialize();

/** Test for required script arguments **/

$verbose = isset( $options['script-verbose'] ) ? true : false;

$scriptVerboseLevel = isset( $options['script-verbose-level'] ) ? $options['script-verbose-level'] : 1;

$troubleshoot = ( isset( $options['script-verbose-level'] ) && $options['script-verbose-level'] > 0 ) ? true : false;

$test = isset( $options['test-only'] ) ? true : false;

$parentNodeID = isset( $options['parent-node-id'] ) ? $options['parent-node-id'] : false;

$contentClassIdentifier = isset( $options['content-class-identifier'] ) ? $options['content-class-identifier'] : false;

$localeIdentifier = isset( $options['locale-identifier'] ) ? $options['locale-identifier'] : 'eng-GB';

/** Script default values **/

$adminUserID = 14;
$updateObjectLanguageCount = 0;
$removeObjectLanguageTranslationsCount = 0;
$offset = 0;
$limit = 100;

/** Script arguments values tests **/

if ( $parentNodeID === false )
{
    $cli->error( "Please specify the content tree parent node ID of a content tree subtree." );
    $script->shutdown( 1 );
}

if ( $contentClassIdentifier === false )
{
    $cli->error( "Please specify a valid content class identifier." );
    $script->shutdown( 1 );
}

if ( $localeIdentifier == '' )
{
    $cli->error( "Please specify a locale identifier." );
    $script->shutdown( 1 );
}

/** Display of execution time **/
function executionTimeDisplay( $srcStartTime, $cli )
{
    /** Add a stoping timing point tracking and calculating total script execution time **/
    $srcStopTime = microtime();
    $startTime = next( explode( " ", $srcStartTime ) ) + current( explode( " ", $srcStartTime ) );
    $stopTime = next( explode( " ", $srcStopTime ) ) + current( explode( " ", $srcStopTime ) );
    $executionTime = round( $stopTime - $startTime, 2 );

    /** Alert the user to how long the script execution took place **/
    $cli->output( "\n\nThis script execution completed in " . $executionTime . " seconds" . ".\n" );
}

/** Login script to run as admin user  This is required to see past content tree permissions, sections and other limitations **/

$currentuser = eZUser::currentUser();
$currentuser->logoutCurrent();
$user = eZUser::fetch( $adminUserID );
$user->loginCurrent();

/** Fetch language to keep **/

$localeLanguageId = eZContentLanguage::idByLocale( $localeIdentifier );

/** Fetch total files count from content tree **/

$totalFileCountParams = array( 'ClassFilterType' => 'include',
                               'ClassFilterArray' => array( $contentClassIdentifier ),
                               'Depth' => 10,
                               'MainNodeOnly' => true,
                               'IgnoreVisibility' => true );

/** Fetch total count for content objects **/

$totalFileCount = eZContentObjectTreeNode::subTreeCountByNodeID( $totalFileCountParams,
    $parentNodeID );

/** Debug verbose output **/

if ( !$totalFileCount )
{
    $cli->error( "No $contentClassIdentifier objects found" );

    /** Call for display of execution time **/
    executionTimeDisplay( $srcStartTime, $cli );

    $script->shutdown( 3 );
}
elseif( $verbose && $totalFileCount > 0 )
{
    $cli->warning( "Total $contentClassIdentifier objects to be checked: " .
        $totalFileCount . "\n" );
}

/** Setup script iteration details **/

$script->setIterationData( '.', '.' );
$script->resetIteration( $totalFileCount );

/** Iterate over nodes **/

$languagesToRemove = array();

while ( $offset < $totalFileCount )
{
    /** Fetch nodes under starting node in content tree **/

    $subTreeParams = array( 'Limit' => $limit,
                            'Offset' => $offset,
                            'ClassFilterType' => 'include',
                            'ClassFilterArray' => array( $contentClassIdentifier ),
                            'SortBy' => array( 'modified', false ),
                            'Depth' => 10,
                            'MainNodeOnly' => true,
                            'IgnoreVisibility' => true );

    /** Optional debug output **/

    if( $troubleshoot && $scriptVerboseLevel >= 5 )
    {
        $cli->output( "$contentClassIdentifier object fetch params: \n");
        $cli->output( print_r( $subTreeParams ) );
    }

    /** Fetch nodes with limit and offset **/

    $subTree = eZContentObjectTreeNode::subTreeByNodeID( $subTreeParams, $parentNodeID );
    $subTreeCount = count( $subTree );

    /** Optional debug output **/

    if( $troubleshoot && $scriptVerboseLevel >= 5 )
    {
        $cli->output( "$contentClassIdentifier objects fetched: ". $subTreeCount ."\n" );

        if( $troubleshoot && $scriptVerboseLevel >= 6 )
        {
            $cli->output( print_r( $subTree ) );
        }
    }

    /** Iterate over nodes **/
    while ( list( $key, $childNode ) = each( $subTree ) )
    {
        $languagesToRemove = array();
        $status = true;

        /** Fetch object details **/
        $childNodeID = $childNode->attribute('node_id');
        $childNodeUrl = $childNode->attribute('url');
        $childNodeObject = $childNode->object();
        $childNodeObjectID = $childNodeObject->attribute( 'id' );

        if( $childNodeObjectID != null )
        {
            $updateObjectLanguageCount++;

            /** Debug verbose output **/
            if( $troubleshoot && $scriptVerboseLevel >= 3 )
            {
                $cli->warning( "\nFound! $contentClassIdentifier object pending language change: " .
                    $childNodeUrl . ", NodeID " . $childNodeID . "\n" );
            }

            /** Only modify object attributes when needed AND when not in test-only mode **/
            if( !$test )
            {
                /** Modify object and update it to use the new default locale language **/
                $contentUpdateLanguageOperationParameters = array(
                    'object_id' => $childNodeObjectID,
                    'new_initial_language_id' => $localeLanguageId,
                    'node_id' => $childNodeID
                );

                /** Set object primary language to the previously defined locale language **/
                $childNodeObject->setAttribute( 'initial_language_id', $localeLanguageId );
                $childNodeObject->store();
                $contentUpdateLanguageOperationResult = eZOperationHandler::execute(
                    'content', 'updateinitiallanguage', $contentUpdateLanguageOperationParameters
                );

                /** Optional debug output **/

                if( $troubleshoot && $scriptVerboseLevel >= 5 )
                {
                    $cli->output( "$contentClassIdentifier objects update params: ");
                    print_r( $updateParams );
                }

                $languages = $childNodeObject->languages();

                /** Iterate over object languages **/
                foreach ( $languages as $languageObject )
                {
                    /** Keep defined language **/
                    if ( $languageObject->attribute( 'id' ) != $localeLanguageId )
                    {
                        $languagesToRemove[] = $languageObject->attribute( 'id' );
                    }
                }

                /** Only remove object / node translations when necessary **/
                if ( count( $languagesToRemove ) )
                {
                    /** Alert the user to the removal of object languages **/
                    $cli->output( "ContentObjectID: " . $childNodeObjectID .
                        " NodeID: " . $childNodeID . " Name: " .
                        $childNodeObject->attribute( 'name' ) . " Languages To Remove: " .
                        print_r( $languagesToRemove ) . "\n" );

                    $contentRemoveTranslationOperationParameters = array(
                        'object_id' => $childNodeObjectID,
                        'language_id_list' => $languagesToRemove,
                        'node_id' => $childNodeID
                    );

                    /** Remove translations **/
                    $contentRemoveTranslationOperationResult = eZOperationHandler::execute(
                        'content', 'removetranslation',
                        $contentRemoveTranslationOperationParameters );
                }

                if( $contentRemoveTranslationOperationResult )
                {
                    $removeObjectLanguageTranslationsCount++;

                    /** Debug verbose output **/

                    if( $verbose )
                    {
                        $cli->output( "Fixed: Removed translations of $contentClassIdentifier object\n");
                    }
                }

                /** Iterate cli script progress tracker **/
                $script->iterate( $cli, $status );
            }
        }
        else
        {
            /** Iterate cli script progress tracker **/
            $script->iterate( $cli, $status );
        }
    }

    /** Iterate fetch function offset and continue **/
    $offset = $offset + $subTreeCount;
}

/** Clear all related caches **/
eZContentCacheManager::clearAllContentCache();
eZUser::cleanupCache();

/** Inform the script user of the results **/
if( $test )
{
    $cli->warning( "\n\nTotal objects found which might need the language to be changed: $updateObjectLanguageCount");
}
else
{
    $cli->warning( "\n\nTotal objects with translations removed: $removeObjectLanguageTranslationsCount");
}

/** Call for display of execution time **/
executionTimeDisplay( $srcStartTime, $cli );

/** Shutdown script **/
$script->shutdown();

?>