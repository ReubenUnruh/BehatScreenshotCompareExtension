<?php

namespace Cevou\Behat\ScreenshotCompareExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

class RawScreenshotCompareContext extends RawMinkContext implements ScreenshotCompareAwareContext
{
    private $screenshotCompareConfigurations;
    private $screenshotCompareParameters;

    /**
     * {@inheritdoc}
     */
    public function setScreenshotCompareConfigurations(array $configurations)
    {
        $this->screenshotCompareConfigurations = $configurations;
    }

    /**
     * {@inheritdoc}
     */
    public function setScreenshotCompareParameters(array $parameters)
    {
        $this->screenshotCompareParameters = $parameters;
    }

    /**
     * @param $sessionName
     * @param $fileName
     * @throws \LogicException
     * @throws \ImagickException
     * @throws \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function compareScreenshot($sessionName, $fileName)
    {
        $this->assertSession($sessionName);

        $session = $this->getSession($sessionName);

        if (!array_key_exists($sessionName, $this->screenshotCompareConfigurations)) {
            throw new \LogicException(sprintf('The configuration for session \'%s\' is not defined.', $sessionName));
        }
        $configuration = $this->screenshotCompareConfigurations[$sessionName];

        /** @var GaufretteFilesystem $targetFilesystem */
        $targetFilesystem = $configuration['adapter'];

        $screenshotDir = $this->screenshotCompareParameters['screenshot_dir'];
        $compareFile = $screenshotDir . DIRECTORY_SEPARATOR . $fileName;
        $sourceFilesystem = new SymfonyFilesystem();

        if (!$sourceFilesystem->exists($compareFile)) {
            throw new FileNotFoundException(null, 0, null, $compareFile);
        }
        
        $this->saveScreenshot('test_' . $fileName, $screenshotDir);
        $testFile = $screenshotDir . DIRECTORY_SEPARATOR . 'test_' . $fileName;

        // Crop the image according to the settings
        // if (array_key_exists('crop', $configuration)) {
            // $crop = $configuration['crop'];
            // $cropWidth = $actualGeometry['width'] - $crop['right']- $crop['left'];
            // $cropHeight = $actualGeometry['height'] - $crop['top'] - $crop['bottom'];
            // $actualScreenshot->cropImage($cropWidth, $cropHeight,$crop['left'],$crop['top']);

            // Refresh geomerty information
            // $actualGeometry = $actualScreenshot->getImageGeometry();
        // }
        
        // $scenarioName = urlencode(str_replace(' ', '_', $event->getStep()->getParent()->getTitle()));
        $diffFile = $screenshotDir . DIRECTORY_SEPARATOR . 'diff_' . $fileName;

        $cmd = 'compare -metric AE ' . $testFile . ' ' . $compareFile . ' ' . $diffFile . ' 2>&1';
        
        $result = shell_exec($cmd);
        
        // $compareScreenshot = new \Gmagick($compareFile);
        // $compareGeometry = $compareScreenshot->getImageGeometry();

        //ImageMagick can only compare files which have the same size
        // if ($actualGeometry !== $compareGeometry) {
            // throw new \ImagickException(sprintf("Screenshots don't have an equal geometry. Should be %sx%s but is %sx%s", $compareGeometry['width'], $compareGeometry['height'], $actualGeometry['width'], $actualGeometry['height']));
        // }

        // $result = $actualScreenshot->compareImages($compareScreenshot, \Imagick::METRIC_ROOTMEANSQUAREDERROR);

        if ($result > 0) {

            throw new \Exception(sprintf("Files are not equal. Diff saved to %s", $diffFile));
        }
    }
}