<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/resources',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        __DIR__ . '/vendor',
        __DIR__ . '/tests',
        __DIR__ . '/config',
        DeclareStrictTypesRector::class => [
            __DIR__ . '/resources/**/*.blade.php',
        ],
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
        AddReturnTypeDeclarationRector::class,
        AddParamTypeDeclarationRector::class,
        AddPropertyTypeDeclarationRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withPhpSets();
