<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;


/**
 * Features context.
 */
class TasksImportContext extends BaseImportContext {

	/**
	 * @BeforeScenario @tasksImport
	 */	
	public static function setUp() {
		self::$watcher = new BackgroundCommandWatcher( 'Tasks', self::$dataFolder );
	}

	/**
	 * @Given /^there is a folder where I can upload tasks$/
	 */
	public function thereIsAFolderWhereICanUploadTasks() {
		$this->assert( is_dir( self::$dataFolder ), 'Target folder does not exist' );
	}

	/**
	 * @Given /^tasks watcher is running$/
	 */
	public function tasksWatcherIsRunning() {
		self::$watcher->start();
	}

	/**
	 * @Given /^there is unimported experiment called "([^"]*)"$/
	 */
	public function thereIsUnimportedExperimentCalled( $experimentName ) {
		$experimentFolder = self::$dataFolder . '/' . $experimentName;
		mkdir( $experimentFolder );

		`cp examples/small-project/*.* $experimentFolder`;
	}

	/**
	 * @When /^I start tasks watcher$/
	 */
	public function iStartTasksWatcher() {
		self::$watcher->start();
	}

	/**
	 * @When /^I upload task called "([^"]*)" to "([^"]*)"$/
	 */
	public function iUploadTaskCalledTo( $taskName, $experimentName ) {
		$taskFolder = self::$dataFolder . '/' . $experimentName . '/' . $taskName;
		mkdir( $taskFolder );

		`cp -r examples/small-project/moses/* $taskFolder`;
	}

	/**
	 * @When /^there is already imported task called "([^"]*)" in "([^"]*)"$/
	 */
	public function thereIsAlreadyImportedTaskCalled( $taskName, $experimentName ) {
		$taskFolder = self::$dataFolder . '/' . $experimentName . '/' . $taskName;
		$importedLock = $taskFolder . '/.imported';

		mkdir( $taskFolder );
		touch( $importedLock );	
	}

	/**
	 * @Given /^there is no task called "([^"]*)" in "([^"]*)"$/
	 */
	public function thereIsNoTaskCalledIn( $taskName, $experimentName ) {
		$experimentFolder = self::$dataFolder . '/' . $experimentName;
		$taskFolder = $experimentFolder . '/' . $taskName;

		if( file_exists( $taskFolder ) ) {
			`rm -rf $taskFolder`;
		}

		$experiments = $this->getMainContext()->getSubcontext( 'nette' )->getService( 'experiments' );
		$experimentId = $experiments->getExperimentByName( $experimentName )->id;

		$tasks = $this->getMainContext()->getSubcontext( 'nette' )->getService( 'tasks' );
		$tasks->deleteTaskByName( $experimentId, $taskName );
	}

	/**
	 * @Then /^tasks watcher should watch that folder$/
	 */
	public function tasksWatcherShouldWatchThatFolder() {
		$pattern = 'Tasks watcher is watching folder: ./data';
		$message =  'Tasks watcher is not watching given folder';

		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should find "([^"]*)" in "([^"]*)"$/
	 */
	public function tasksWatcherShouldFindIn( $taskName, $experimentName ) {
		$pattern = $this->getWatcherMessage( $taskName, $experimentName );
		$message = 'New task was not found';

		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should not find "([^"]*)" in "([^"]*)"$/
	 */
	public function tasksWatcherShouldNotFindIn( $taskName, $experimentName ) {
		$pattern = $this->getWatcherMessage( $taskName, $experimentName );
		$message = 'New task was found';

		$this->assertLogDoesNotContain( $pattern, $message );
	}

	/**
	 * @Then /^task watcher should find "([^"]*)" in "([^"]*)" only once$/
	 */
	public function taskWatcherShouldFindInOnlyOnce( $taskName, $experimentName ) {
		$pattern = $this->getWatcherMessage( $taskName, $experimentName );
		$message = 'New task was found more than once';
		
		$this->assertLogContainsExactlyOccurences( $pattern, $message, 1 );
	}

	private function getWatcherMessage( $taskName, $experimentName ) {
		return sprintf(  'New task called %s was found in experiment %s', $taskName, $experimentName );
	}

	/**
	 * @Then /^tasks watcher should use "([^"]*)" for "([^"]*)" in "([^"]*)"$/
	 */
	public function tasksWatcherShouldUseForIn( $file, $resource, $taskName ) {
		$pattern = "$file will be used as a $resource source in $taskName";		
		$message = "Using bad source for $resource"; 

		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should complain about missing "([^"]*)" for "([^"]*)"$/
	 */
	public function tasksWatcherShouldComplainAboutMissingFor( $resource, $taskName ) {
		$pattern = "Missing $resource in $taskName";
		$message = "Missing file with $resource sentences";
		
		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should (not )?parse "([^"]*)" in "([^"]*)" for "([^"]*)"$/
	 */
	public function tasksWatcherShouldParseInFor( $shouldNotParse, $resource, $file, $taskName ) {
		$pattern = "Starting parsing of $resource located in $file for $taskName";
		$message = "Parsing of resources didn't start";

		if( $shouldNotParse ) {
			$this->assertLogDoesNotContain( $pattern, $message );
		} else {
			$this->assertLogContains( $pattern, $message );
		}
	}

	/**
	 * @Given /^tasks watcher should abort parsing of "([^"]*)"$/
	 */
	public function tasksWatcherShouldAbortParsingOf( $taskName ) {
		$pattern = "Parsing of $taskName aborted!";
		$message = "Parsing of task aborted due to incorrect input";

		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should say that "([^"]*)" has (\d+) "([^"]*)"$/
	 */
	public function tasksWatcherShouldSayThatHas( $taskName, $count, $resource ) {
		$pattern = "$taskName has $count $resource";
		$message = "Number of resources parsed correctly";
		
		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Then /^tasks watcher should say that "([^"]*)" has bad translations sentences count$/
	 */
	public function tasksWatcherShouldSayThatHasBadTranslationsSentencesCount( $taskName ) {
		$pattern = "$taskName has bad number of sentences";
		$message = "Number of translation/reference sentences doesn't match";

		$this->assertLogContains( $pattern, $message );
	}

	/**
	 * @Given /^task "([^"]*)" is uploaded successfully$/
	 */
	public function taskIsUploadedSuccessfully( $taskName ) {
		$pattern = "Task $taskName uploaded successfully";
		$message = "Task is not uploaded successfully";
		
		$this->assertLogContains( $pattern, $message );
	}

}
