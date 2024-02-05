<?php
class QuickPoster{
    protected $di_dbase;
    public readonly string $post_path;

    public function __construct(\Database\Database $dbase)
    {
        $this->di_dbase = $dbase;
    }

    /**
     * Create a post
     * @param array $post_array
     * @return bool true if post was created
     */
    public function createPost(\Config $config, array $post_array): bool
    {
        print_rob("inside createPost", false);
        print_rob($post_array, false);

        /* $post_array = Array
(
    [time] => 20:00
    [date] => Friday 2 February 2024
    [title] => Creating posts with Posternator
    [post_content] => I'm really glad to have this working. I can now create posts from the web interface.

    I just need to create one more class or so to actually save the posts.

    Just a simple matter of programming!
) */
        // Parse the date and time
        $date = $post_array['date'];
        $time = $post_array['time'];
        $title = $post_array['title'];
        $tags = $post_array['tags'];
        // remove ^M from the end of the lines of the content
        $content = preg_replace("/\r/", "", $post_array['post_content']);

        $file_path = $this->createFilePath($title, $date, $config);

        $frontmatter = $this->createFrontMatter($title, $date, $time, $tags);

        // Create file path if it doesn't exist
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = fopen($file_path, "w");
        // write time and date at top of the file
        fwrite($file, $frontmatter);
        fwrite($file, "\n");
        fwrite($file, $content);
        fclose($file);

        // return path after removing the app path
        $this->post_path = str_replace($config->post_path_journal, "", $file_path);

        return true;

    }

    private function createFrontMatter(string $title, string $date, string $time, string $tags): string
    {
        $dateObject = new DateTime($date);

        $year = $dateObject->format('Y');
        $month = $dateObject->format('m');
        $day = $dateObject->format('d');

        $frontmatter = "---\n";
        $frontmatter .= "title: \"$title\"\n";
        // "life, journal, fun" => ["life", "journal", "fun"]
        $quoted_tags = '"' . preg_replace("/, /", "\", \"", $tags) . '"';
        $frontmatter .= "tags: [ \"$year\", $quoted_tags ]\n";
        $frontmatter .= "author: Rob Nugen\n";
        $frontmatter .= "date: $year-$month-$day"."T$time:00+09:00\n";      // :00 so Hugo will parse datetime properly
        $frontmatter .= "draft: false\n";
        $frontmatter .= "---\n";

        return $frontmatter;
    }
    private function createFilePath(string $title, string $date, \Config $config): string
    {
        $url_title = $this->createUrlTitle($title);
        // Parse $date = 'Saturday 3 February 2024 JST' to date so we can get numeric year month and day
        $dateObject = new DateTime($date);

        $year = $dateObject->format('Y');
        $month = $dateObject->format('m');
        $day = $dateObject->format('d');

        $file_path = "$config->post_path_journal/$year/$month/$day$url_title.md";

        return $file_path;
    }

    private function createUrlTitle(string $title): string
    {
        // remove single quotes so I'm and It's don't become I-m and It-s
        $url_title = preg_replace("/'/", "", $title);

        // replace "?' " with "-"
        $url_title = preg_replace("/[^a-zA-Z0-9\w]/", "-", $url_title);

        // replace multiple hyphens with a single hyphen
        $url_title = preg_replace("/-+/", "-", $url_title);

        // remove leading and trailing hyphens
        $url_title = trim($url_title, "-");

        // convert to lowercase
        $url_title = strtolower($url_title);

        return $url_title;
    }
}
