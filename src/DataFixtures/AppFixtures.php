<?php

namespace App\DataFixtures;

use App\Entity\Articles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $article = new Articles();
        $article->setTitle('First Article');
        $article->setContent(' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis et nunc vel velit congue vehicula nec et nunc. Nunc vestibulum justo sed augue fringilla, vel condimentum eros tempor. Nam sed venenatis nulla. Cras tortor massa, sodales in metus mollis, posuere tincidunt enim. Praesent malesuada rutrum elit, quis accumsan mi interdum ut. Cras gravida mollis diam, ut interdum eros elementum non. Cras bibendum enim dui, non accumsan dolor hendrerit non. In tincidunt, enim non scelerisque aliquam, ligula augue dapibus velit, ut semper eros massa et neque. Mauris tristique mi ut velit tincidunt, ac auctor turpis finibus. Aliquam eu neque non lacus euismod iaculis sed ac metus. Duis sagittis sem urna. Ut ut dictum tortor. Aliquam aliquet pharetra sem id semper. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec sodales ex sit amet vestibulum ultricies. Aenean in consectetur sem. Pellentesque faucibus dignissim velit et hendrerit. Donec consectetur porttitor purus nec accumsan. Maecenas mattis urna non suscipit mattis. Nullam feugiat tortor erat, nec varius leo imperdiet ut. Aenean porta lorem et lacus blandit blandit. Etiam felis turpis, pharetra sagittis nisl vitae, luctus rutrum turpis. Vivamus eget varius lorem, id facilisis ex. ');

        $manager->persist($article);

        $article = new Articles();
        $article->setTitle('Second Article');
        $article->setContent(' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis et nunc vel velit congue vehicula nec et nunc. Nunc vestibulum justo sed augue fringilla, vel condimentum eros tempor. Nam sed venenatis nulla. Cras tortor massa, sodales in metus mollis, posuere tincidunt enim. Praesent malesuada rutrum elit, quis accumsan mi interdum ut. Cras gravida mollis diam, ut interdum eros elementum non. Cras bibendum enim dui, non accumsan dolor hendrerit non. In tincidunt, enim non scelerisque aliquam, ligula augue dapibus velit, ut semper eros massa et neque. Mauris tristique mi ut velit tincidunt, ac auctor turpis finibus. Aliquam eu neque non lacus euismod iaculis sed ac metus. Duis sagittis sem urna. Ut ut dictum tortor. Aliquam aliquet pharetra sem id semper. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Donec sodales ex sit amet vestibulum ultricies. Aenean in consectetur sem. Pellentesque faucibus dignissim velit et hendrerit. Donec consectetur porttitor purus nec accumsan. Maecenas mattis urna non suscipit mattis. Nullam feugiat tortor erat, nec varius leo imperdiet ut. Aenean porta lorem et lacus blandit blandit. Etiam felis turpis, pharetra sagittis nisl vitae, luctus rutrum turpis. Vivamus eget varius lorem, id facilisis ex. ');

        $manager->persist($article);

        $manager->flush();
    }
}
