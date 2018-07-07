<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Rename extends Command
{

    protected $separator = '~';

    protected function configure()
    {
        $this->setName('mp3:rename')->setDescription('重命名mp3文件');
        $this->addArgument('path', InputArgument::REQUIRED, '待重命名的mp3文件所在的目录');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $path = trim($input->getArgument('path'));
            if (!$path) {
                throw new \RuntimeException('请指定path');
            }
            if (!file_exists($path)) {
                throw new \RuntimeException($path . '不存在');
            }
            if (!is_dir($path)) {
                throw new \RuntimeException($path . '不是目录');
            }
            $path = realpath($path) . DIRECTORY_SEPARATOR;
            $counter = 0;
            foreach (scandir($path) as $file) {
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'mp3') {
                    $output->writeln($file);
                    $this->rename($path . $file);
                    $counter++;
                }
            }
            $output->writeln("成功重命名了" . $counter . "个文件");
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    function rename($filename)
    {

        //
//        dump(json_decode(json_encode($id3['tags_html']['id3v2']['artist'][0])));
//        return ;
//
//
//
//        $response = new \MP3_Id3($filename);
//        dump($response->getTags());
//        return ;


        $id3 = (new \getID3())->analyze($filename);
        if (isset($id3['tags_html']['id3v2']['album'])) {
            if (count($id3['tags_html']['id3v2']['album']) == 1) {
                $album = $id3['tags_html']['id3v2']['album'][0];
            } else {
                $album = explode(' ', $id3['tags_html']['id3v2']['album']);
            }
        } else {
            $album = '未知专辑';
        }
        if (isset($id3['tags_html']['id3v2']['artist'])) {
            if (count($id3['tags_html']['id3v2']['artist']) == 1) {
                $artist = $id3['tags_html']['id3v2']['artist'][0];
            } else {
                $artist = explode(' ', $id3['tags_html']['id3v2']['artist']);
            }
        } else {
            $artist = '未知艺术家';
        }
        if (isset($id3['tags_html']['id3v2']['title'])) {
            if (count($id3['tags_html']['id3v2']['title']) == 1) {
                $title = $id3['tags_html']['id3v2']['title'][0];
            } else {
                $title = explode(' ', $id3['tags_html']['id3v2']['title']);
            }
        } else {
            $title = pathinfo($filename, PATHINFO_FILENAME);
        }

        if (isset($id3['tags_html']['id3v2']['track_number'])) {
            if (count($id3['tags_html']['id3v2']['track_number']) == 1) {
                $track_number = $id3['tags_html']['id3v2']['track_number'][0];
            } else {
                $track_number = explode(' ', $id3['tags_html']['id3v2']['track_number']);
            }
        } else {
            $track_number = 0;
        }

        if ($track_number) {
            $title .= $track_number . '.' . $title;
        }

        $newFilename = dirname($filename) . DIRECTORY_SEPARATOR;
        $newFilename .= $this->path($album) . $this->separator;
        $newFilename .= $this->path($artist) . $this->separator;
        $newFilename .= $this->path($title) . '.mp3';
        if ($this->separator == DIRECTORY_SEPARATOR) {
            $dir = dirname($newFilename);
            if (!file_exists($dir)) {
                mkdir($dir, null, true);
            }
        }
        rename($filename, $newFilename);
    }

    function path($path)
    {
        return mb_convert_encoding(str_replace([
            '/',
            '\\',
            ':',
            '*',
            '"',
            '|',
            '<',
            '>',
        ], '-', $path), "utf-8", 'HTML-ENTITIES');
    }
}