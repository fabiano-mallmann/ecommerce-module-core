<?php

namespace Mundipagg\Core\Test\Functional\Features\Bootstrap;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\MinkExtension\Context\MinkContext;


/**
 * Features context.
 */
class CoreFeature extends MinkContext
{
    /**
     *
     * @var Behat\Gherkin\Node\StepNode
     */
    protected $currentStep = null;
    protected $scenarioTokens = null;
    protected static $featureHash = null;
    protected $screenshotDir = DIRECTORY_SEPARATOR . 'tmp';

    /**
     *
     * @AfterStep
     * @param     $event
     */
    protected function afterStepFailureScreenshot($event)
    {
        $e = $event->getTestResult()->getCallResult()->getException();
        if ($e) {
            if (!file_exists($this->screenshotDir)) {
                mkdir($this->screenshotDir);
            }
            $filename = tempnam($this->screenshotDir, "failure_screenshoot_");
            unlink($filename);
            $filename .= ".png";
            $this->screenshot($filename);
            echo "saved failure screenshot to '$filename'";
        }
    }

    /**
     *
     * @BeforeFeature
     */
    protected static function beforeFeature($event)
    {
        self::$featureHash = null;
        $requestTime = $_SERVER['REQUEST_TIME'];
        $featureTitle = $event->getFeature()->getTitle();
        $hash = hash('sha512', $featureTitle . $requestTime);
        self::$featureHash = substr($hash, 0, 16);
    }


    /**
     *
     * @BeforeScenario
     */
    protected function beforeScenario($event)
    {
        if ($event->getScenario()->hasTag('smartStep')) {
            /*throw new PendingException(
                'This is a partial @smartStep Scenario and should not be isolatedly executed.'
            );*/
        }

        $this->scenarioTokens = null;
        try {
            //trying to save examples to use in @smartStep
            $this->scenarioTokens =
                $event->getScenario()->getTokens();
        }catch(Throwable $e) {
        }
    }


    /**
     *
     * @BeforeStep
     */
    protected function beforeStep($event)
    {
        $this->currentStep  = $event->getStep();
    }

    /**
     * Show an animation when waiting for a step
     *
     * @param int   $remaning Amount in seconds remaing on wait.
     * @param float $interval in seconds to update animation frame.
     */
    protected function spinAnimation($remaining = null, $interval = 0.1)
    {
        static $frameId = null;
        $currentTime = microtime(true);
        static $lastUpdate = null;

        if($frameId === null) {
            $frameId = 0;
        }

        if($lastUpdate === null) {
            $lastUpdate = $currentTime;
        }

        if($currentTime - $lastUpdate < $interval) {
            return;
        }
        $lastUpdate = $currentTime;

        switch($frameId) {
            default: $frameId = 0;
            case 0: $frame = '|';
                break;
            case 1: $frame = '\\';
                break;
            case 2: $frame = '--';
                break;
            case 3: $frame = '/';
                break;

        }
        $frameId++;

        if($this->currentStep !== null) {

            print "'" . $this->currentStep->getText() . "' - ";
        }
        if($remaining !== null) {
            print "$remaining seconds remaining...  ";
        }
        print "$frame             \r";
        flush();
    }


    /**
     * Based on example from http://docs.behat.org/en/v2.5/cookbook/using_spin_functions.html
     *
     * @param  callable $lambda The callback that will be called in spin
     * @param  int      $wait   Amount in seconds to spin timeout
     * @return bool
     * @throws Exception
     */
    protected function spin(callable $lambda, $wait = 60)
    {
        $startTime = time();
        do{
            try {
                if($lambda($this)) {
                    return true;
                }
            }catch(Exception $e) {
                //do nothing;
            }
            usleep(100000);
            $this->spinAnimation($wait - (time() - $startTime));
        }while(time() < $startTime + $wait);

        throw new Exception(
            "Timeout: $wait seconds."
        );
    }



}
