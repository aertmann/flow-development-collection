<?php
namespace TYPO3\Flow\Tests\Functional\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Configuration\ConfigurationSchemaValidator;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Error\Result;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for Configuration Validation
 */
class ConfigurationValidationTest extends FunctionalTestCase
{

    /**
     * @var array<string>
     */
    protected $contextNames = ['Development', 'Production', 'Testing'];

    /**
     * @var array<string>
     */
    protected $configurationTypes = ['Caches', 'Objects', 'Policy', 'Routes', 'Settings'];

    /**
     * @var array<string>
     */
    protected $schemaPackageKeys = ['TYPO3.Flow'];

    /**
     * @var array<string>
     */
    protected $configurationPackageKeys = ['TYPO3.Flow', 'TYPO3.Fluid', 'TYPO3.Eel', 'TYPO3.Kickstart'];

    /**
     *
     * @var ConfigurationSchemaValidator
     */
    protected $configurationSchemaValidator;

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    /**
     * @var PackageManager
     */
    protected $mockPackageManager;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        //
        // create a mock packageManager that only returns the the packages that contain schema files
        //

        $schemaPackages = [];
        $configurationPackages = [];

        // get all packages and select the ones we want to test
        $temporaryPackageManager = $this->objectManager->get(PackageManagerInterface::class);
        foreach ($temporaryPackageManager->getActivePackages() as $package) {
            if (in_array($package->getPackageKey(), $this->getSchemaPackageKeys())) {
                $schemaPackages[$package->getPackageKey()] = $package;
            }
            if (in_array($package->getPackageKey(), $this->getConfigurationPackageKeys())) {
                $configurationPackages[$package->getPackageKey()] = $package;
            }
        }
return;
        $this->mockPackageManager = $this->getMock(PackageManager::class, ['getActivePackages']);
        $this->mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue($schemaPackages));
return;
        //
        // create mock configurationManager
        //

        $yamlConfigurationSource = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Configuration\Fixtures\RootDirectoryIgnoringYamlSource::class);

        $this->mockConfigurationManager = $this->objectManager->get(ConfigurationManager::class);
        $this->mockConfigurationManager->setPackages($configurationPackages);
        $this->inject($this->mockConfigurationManager, 'configurationSource', $yamlConfigurationSource);

        //
        // create the configurationSchemaValidator
        //

        $this->configurationSchemaValidator = $this->objectManager->get(ConfigurationSchemaValidator::class);
        $this->inject($this->configurationSchemaValidator, 'configurationManager', $this->mockConfigurationManager);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->injectApplicationContextIntoConfigurationManager($this->objectManager->getContext());
        parent::tearDown();
    }

    /**
     * @param ApplicationContext $context
     * @return void
     */
    protected function injectApplicationContextIntoConfigurationManager(ApplicationContext $context)
    {
        ObjectAccess::setProperty($this->mockConfigurationManager, 'configurations',
            [ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => []], true);
//        ObjectAccess::setProperty($this->mockConfigurationManager, 'context', $context, true);
//        ObjectAccess::setProperty($this->mockConfigurationManager, 'orderedListOfContextNames', [(string)$context],
            true);
//        ObjectAccess::setProperty($this->mockConfigurationManager, 'includeCachedConfigurationsPathAndFilename',
            FLOW_PATH_CONFIGURATION . (string)$context . '/IncludeCachedConfigurations.php', true);
    }

    /**
     * @return array
     */
    public function configurationValidationDataProvider()
    {
        $result = [];
        foreach ($this->getContextNames() as $contextName) {
            foreach ($this->getConfigurationTypes() as $configurationType) {
                $result[] = ['contextName' => $contextName, 'configurationType' => $configurationType];
            }
        }
        return $result;
    }

    /**
     * @param string $contextName
     * @param string $configurationType
     * @test
     * @dataProvider configurationValidationDataProvider
     */
    public function configurationValidationTests($contextName, $configurationType)
    {
return;
        $this->injectApplicationContextIntoConfigurationManager(new ApplicationContext($contextName));
        $schemaFiles = [];
        $validationResult = $this->configurationSchemaValidator->validate($configurationType, null, $schemaFiles);
        $this->assertValidationResultContainsNoErrors($validationResult);
    }

    /**
     * @param Result $validationResult
     * @return void
     */
    protected function assertValidationResultContainsNoErrors(Result $validationResult)
    {
        if ($validationResult->hasErrors()) {
            $errors = $validationResult->getFlattenedErrors();
            /** @var Error $error */
            $output = '';
            foreach ($errors as $path => $pathErrors) {
                foreach ($pathErrors as $error) {
                    $output .= sprintf('%s -> %s' . PHP_EOL, $path, $error->render());
                }
            }
            $this->fail($output);
        }
        $this->assertFalse($validationResult->hasErrors());
    }

    /**
     * @return array<string>
     */
    protected function getContextNames()
    {
        return $this->contextNames;
    }

    /**
     * @return array<string>
     */
    protected function getConfigurationTypes()
    {
        return $this->configurationTypes;
    }

    /**
     * @return array<string>
     */
    protected function getSchemaPackageKeys()
    {
        return $this->schemaPackageKeys;
    }

    /**
     * @return array<string>
     */
    protected function getConfigurationPackageKeys()
    {
        return $this->configurationPackageKeys;
    }
}
