<?php
class QuickPoster{
    protected $di_dbase;

    public function __construct(\Database\Database $dbase)
    {
        $this->di_dbase = $dbase;
    }

    /**
     * Create a post
     * @param array $post_array
     */
    public function createPost(array $post_array)
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
        $url_title = $this->createUrlTitle($title);
        // remove ^M from the end of the lines of the content
        $content = preg_replace("/\r/", "", $post_array['post_content']);

        // Parse $date = 'Saturday 3 February 2024 JST' to date so we can get numeric year month and day
        $dateObject = new DateTime($date);

        $year = $dateObject->format('Y');
        $month = $dateObject->format('m');
        $day = $dateObject->format('d');

        $file_path = "$config->app_path/public/journal/$year/$month/$day$url_title.txt";

        // Create file path if it doesn't exist
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $file = fopen($file_path, "w");
        // write time and date at top of the file
        fwrite($file, "#### $time $date\n");
        fwrite($file, $content);
        fclose($file);

    }

    private function createUrlTitle(string $title): string
    {
        // replace "?' " with "-"
        $url_title = preg_replace("/[^a-zA-Z0-9\w]/", "-", $title);

        // replace multiple hyphens with a single hyphen
        $url_title = preg_replace("/-+/", "-", $url_title);

        // remove leading and trailing hyphens
        $url_title = trim($url_title, "-");

        // convert to lowercase
        $url_title = strtolower($url_title);

        return $url_title;
    }
}
