<?php

use Illuminate\Database\Seeder;
use Symfony\Component\Finder\Finder;
use Faker\Factory as Faker;
use App\Photo;

class PhotosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $finder = new Finder();
        $finder->files()->in(storage_path('app/public/gallery/pictures'));

 

        foreach ($finder as $file) {

            $imageSize = getimagesize(storage_path('app/public/gallery/pictures/' . $file->getRelativePathname()));

            $widthToHeightRatio = $imageSize[0] / $imageSize[1];

            $photo = Photo::create([

            'name' => $faker->realText(20),
            'description' => $faker->realText(40),
            'original' => asset('storage/gallery/pictures/' . $file->getRelativePathname()),
            'thumbnail' => asset('storage/gallery/pictures/' . $file->getRelativePathname()),
            'storage_path' => 'public/gallery/pictures/' . $file->getRelativePathname(),
            'width_to_height_ratio' => $widthToHeightRatio


          ]);

            $photo->galleryAlbum()->associate(rand(1, 5));

            $photo->save();
        }
    }
}
