<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Write extends Command
{

    protected $separator = '~';

    protected function configure()
    {
        $this->setName('id3:write')->setDescription('写入id3信息');
        $this->addArgument('path', InputArgument::REQUIRED, '待写入id3信息的mp3文件所在的目录');
        $this->addOption('album', null, InputOption::VALUE_OPTIONAL, '专辑');
        $this->addOption('artist', null, InputOption::VALUE_OPTIONAL, '艺术家');
        $this->addOption('track', null, InputOption::VALUE_OPTIONAL, '轨道');
        $this->addOption('title', null, InputOption::VALUE_OPTIONAL, '标题');
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
                    $this->rename($path . $file, $input);
                    $counter++;
                }
            }
            $output->writeln("成功写入了" . $counter . "个文件");
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    function rename(string $filename, InputInterface $input)
    {
        $string = pathinfo($filename, PATHINFO_FILENAME);
        $array = explode($this->separator, $string);
        $tags = array(
            'artist' => call_user_func(function (InputInterface $input) {
                if ($artist = $input->getOption('artist')) {
                    return $artist;
                }
                return $array[1] ?? '未知艺术家';
            }, $input),
            'title' => $title = $array[2] ?? $filename,
            'album' => call_user_func(function (InputInterface $input) {
                if ($album = $input->getOption('album')) {
                    return $album;
                }
                return $array[0] ?? '未知专辑';
            }, $input),
            'title' => call_user_func(function (InputInterface $input) use ($filename) {
                if ($title = $input->getOption('title')) {
                    if (preg_match("/$title/i", pathinfo($filename, PATHINFO_FILENAME), $match)) {
                        return $match[0];
                    }
                    return pathinfo($filename, PATHINFO_FILENAME);
                }
                return pathinfo($filename, PATHINFO_FILENAME);
            }, $input),
            'band' => '',
            'publisher' => '',
            'genre' => '',
            'year' => '',
            'track_number' => call_user_func(function (InputInterface $input) use ($filename) {
                if ($track_number = $input->getOption('track')) {
                    if (preg_match("/$track_number/i", pathinfo($filename, PATHINFO_FILENAME), $match)) {
                        return $match[0];
                    }
                    return '';
                }
                return '';
            }, $input),
            'bpm' => '',
            'initial_key' => '',
        );


        $textEncoding = 'UTF-8';
        $getID3 = new \getID3();
        $getID3->setOption(array('encoding' => $textEncoding));

        $tagwriter = new  \getid3_writetags;
        $tagwriter->filename = $filename;
        $tagwriter->tagformats = ['id3v2.3'];

        // set various options (optional)
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = $textEncoding;
        $tagwriter->remove_other_tags = true;


        $tagData['comment'] = ['制作:awz@awz.cn'];
        foreach ($tags as $tag => $value) {
            $tagData[$tag] = [$value];
        }
        $tagwriter->tag_data = $tagData;
        $tagwriter->WriteTags();
        if (!empty($tagwriter->errors)) {
            throw new \Exception(implode(PHP_EOL, $tagwriter->errors));
        }
    }
}