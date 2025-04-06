<?php
class QuickPoster{
    public readonly string $post_path;

    public function __construct(
        private int $debug,
    ) {
    }

    /**
     * Create a post
     * @param array $post_array
     * @return bool true if post was created
     */
    public function createPost(\Config $config, array $post_array): bool
    {
        if($this->debug > 2){
            print_rob(object: "inside createPost", exit: false);
            print_rob(object: $post_array, exit: false);
        }

        /* $post_array = Array
(
    [time] => 20:00
    [date] => Friday 2 February 2024
    [title] => Creating posts with Quick
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
        $content = preg_replace(pattern: "/\r/", replacement: "", subject: $post_array['post_content']);

        $file_path = $this->createFilePath(title: $title, date: $date, config: $config);

        $frontmatter = $this->createFrontMatter(title: $title, date: $date, time: $time, tags: $tags);

        // Create file path if it doesn't exist
        $dir = dirname(path: $file_path);
        if (!is_dir(filename: $dir)) {
            mkdir(directory: $dir, permissions: 0755, recursive: true);
        }
        $file = fopen(filename: $file_path, mode: "w");
        // write time and date at top of the file
        fwrite(stream: $file, data: $frontmatter);
        fwrite(stream: $file, data: "\n");
        fwrite(stream: $file, data: $content);
        fclose(stream: $file);

        // return path after removing the app path
        $this->post_path = str_replace(search: $config->post_path_journal, replace: "", subject: $file_path);

        return true;
    }

    private function createFrontMatter(string $title, string $date, string $time, string $tags): string
    {
        $dateObject = new DateTime(datetime: $date);

        $year = $dateObject->format(format: 'Y');
        $month = $dateObject->format(format: 'm');
        $day = $dateObject->format(format: 'd');

        $frontmatter = "---\n";
        $frontmatter .= "title: \"$title\"\n";
        // "life, journal, fun" => ["life", "journal", "fun"]
        $quoted_tags = '"' . preg_replace(pattern: "/, /", replacement: "\", \"", subject: $tags) . '"';
        $frontmatter .= "tags: [ \"$year\", $quoted_tags ]\n";
        $frontmatter .= "author: Rob Nugen\n";
        $frontmatter .= "date: $year-$month-{$day}T$time:00+09:00\n";      // :00 so Hugo will parse datetime properly
        $frontmatter .= "draft: false\n";
        $frontmatter .= "---\n";

        return $frontmatter;
    }

    private function createFilePath(string $title, string $date, \Config $config): string
    {
        $url_title = $this->createUrlTitle(title: $title);
        // Parse $date = 'Saturday 3 February 2024 JST' to date so we can get numeric year month and day
        $dateObject = new DateTime(datetime: $date);

        $year = $dateObject->format(format: 'Y');
        $month = $dateObject->format(format: 'm');
        $day = $dateObject->format(format: 'd');

        $file_path = "$config->post_path_journal/$year/$month/$day$url_title.md";

        return $file_path;
    }

    private function createUrlTitle(string $title): string
    {
        // remove single quotes so I'm and It's don't become I-m and It-s
        $url_title = preg_replace(pattern: "/'/", replacement: "", subject: $title);

        // replace "?' " with "-"
        $url_title = preg_replace(pattern: "/[^a-zA-Z0-9\w]/", replacement: "-", subject: $url_title);

        // replace multiple hyphens with a single hyphen
        $url_title = preg_replace(pattern: "/-+/", replacement: "-", subject: $url_title);

        // remove leading and trailing hyphens
        $url_title = trim(string: $url_title, characters: "-");

        // convert to lowercase
        $url_title = strtolower(string: $url_title);

        return $url_title;
    }
}
