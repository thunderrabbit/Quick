#!/bin/bash

if [ "$#" -lt 1 ]; then
    echo "Usage: $0 file1.php [file2.php ...]"
    exit 1
fi

for file in "$@"; do
    echo "Copy the following for $file:"
    echo '```php'
    cat "$file"
    echo '```'
    echo -e "\n------------------------\n"
done
