<?php

namespace Acquia\RecipeBuilder\Drush\Commands;

use Composer\Util\ProcessExecutor;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Boot\Kernels;
use Drush\Commands\core\SiteInstallCommands;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * Ask questions for the recipes.
 */
class RecipeBuilderDrushCommands extends DrushCommands {

  const BUILDER = 'recipe:builder';


  /**
   * Do not generate settings.php file to site1.
   */
  #[CLI\Hook(type: HookManager::INTERACT, target: self::BUILDER)]
  public function askQuestions(ArgvInput $input, ConsoleOutput $output) {
    $project_dir = dirname(__DIR__ , 3);
    $logo = file_get_contents($project_dir . "/assets/logo.txt");
    $this->io()->text("<fg=green;options=bold>$logo</>");
    $this->io()->text("<fg=cyan;options=underscore>Welcome to the Acquia Recipe Builder</>");
    $this->io()->text("");
    $collections = Yaml::parseFile($project_dir . "/recipe-conf/build.yml");
    $table = new Table($output);
    $table->setHeaders(['ID', 'Recipe', 'Description']);
    $dishes = $collections['dishes'];
    $total = count($dishes);
    $key = 0;
    foreach ($dishes as $id => $dish) {
      $table->addRow([$id, $dish['name'], $dish['description']]);
      if ($key + 1 != $total) {
        $table->addRow(["", "", ""]);
      }
      $key++;
    }
    $table->setColumnMaxWidth(2, 81);
    $table->setStyle('box');
    $table->render();

//    $input = new ArgvInput();
//    $output = new ConsoleOutput();

    // Define the question
    $questionText = 'Choose Site Recipes';
//    $question = new Question($questionText);
//    $question->setAutocompleterValues(array_keys($dishes));

    $question = new ChoiceQuestion($questionText, array_keys($dishes));
    $question->setMultiselect(TRUE);

    $base_recipe = $this->io()->askQuestion($question);

//    $question = new ChoiceQuestion("Choose Add-ons", array_keys($collections['add_ons']));
//    $question->setMultiselect(TRUE);
//    $add_ons = $this->io()->askQuestion($question);

    $add_ons = [];
    foreach ($collections['add_ons'] as $key => $add_on_arr) {
      $default = $add_on_arr['default'] ?? TRUE;
      $question = new Question($add_on_arr['name'], $default ? "yes" : "no");
      $question->setAutocompleterValues(['yes', 'no']);
      $is_needed = $this->io()->askQuestion($question);
      if ($is_needed == "yes") {
        $add_ons[] = $key;
      }
    }

    foreach ($add_ons as $add_on) {
      $recipes = $collections['add_ons'][$add_on]['recipes']['require'];
      foreach ($recipes as $recipe) {
        $process = new ProcessExecutor();
        $project = dirname(getcwd());
        //$process->executeTty("composer require $recipe --dry-run -d $project");
        $process->executeTty("composer require $recipe -d $project");
      }
    }
    $this->io()->text("");
    $this->io()->success("Recipes downloaded successfully.");
  }

  /**
   * Do not generate settings.php file to site1.
   */
  #[CLI\Command(name: self::BUILDER, aliases: ['rb', 'sin', 'recipe-builder'])]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  #[CLI\Kernel(name: Kernels::INSTALLER)]
  public function build(): void {}


}
