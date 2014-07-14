<?php

$runner->addTestsFromDirectory(__DIR__.'/src/M6Web/Component/Tests');

$script->excludeDirectoriesFromCoverage([__DIR__.'/vendor']);