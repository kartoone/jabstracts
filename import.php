<pre>
<?php
$row = 1;
$headers = [];
$alldata = [];
$newheaders = ["section", "type", "title", "presenter", "authorstr", "abstract"];
$newdata = [$newheaders];
if (($handle = fopen("abstracts18.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        if ($row==1) {
            // let's figure out the column headers
            for ($c=0; $c < $num; $c++) {
                $headers[] = trim($data[$c]);
            }   
        } else {
            // let's put all the data into the next record
            $sub = [];
            for ($c=0; $c < $num; $c++) {
                $sub[$headers[$c]] = trim($data[$c]);
            }
            $alldata[] = $sub;
        }
        $row++;
    }
    fclose($handle);
}
echo count($alldata) . ' records read.<br />';
foreach ($alldata as $sub) {
    $newdata[] = processSub($sub);
}
echo count($newdata) . ' records processed.<br />';
$fp = fopen('processed18.csv', 'w');
foreach ($newdata as $r) {
    fputcsv($fp,$r);
}
fclose($fp);

function processSub($sub) {
    print_r($sub);
    $s = [];
    $presenter = strtoupper($sub['pfirst'] . ' ' . $sub['plast']);
    $section1 = str_replace("\t", " ", $sub['section']);
    $section = str_replace(". ", ".", $section1);
    $title = strtoupper($sub['title']);
    $authorstr = processAuthors($sub, $presenter);
    $abstract = $sub['abstract'];
    return [$section, $sub['type'], $title, $presenter, $authorstr, $abstract];
}

// complicated ...
// basic idea - put all the authors into array that maps from institution to list of authors
// [0=>'Samford University',['Brian Toone', 'John Smith', etc...]]
function processAuthors($sub, $presenter) {
    $authors = [0=>[strtoupper($sub['pinstitution']),[$presenter]]];
    addAuthor($sub['coauthor1'],$sub['coauthorinst'],$authors);
    addAuthor($sub['coauthor2'],$sub['coauthorinst'],$authors);
    addAuthor($sub['coauthor3'],$sub['coauthorinst'],$authors);
    addAuthor($sub['coauthor4'],$sub['coauthorinst'],$authors);
    addAuthor($sub['coauthor5'],$sub['coauthorinst'],$authors);
    addAuthor($sub['coauthor6'],$sub['coauthorinst'],$authors);
    
    // process author inst first
    $inst = $authors[0];
    $insta = $inst[1];
    $authorstr = "";
    if (count($insta)==1) {
        // only the presenter ... no coauthors at all
        $authorstr .= ", {$inst[0]}.";
    } else if (count($insta)==2) {
        $authorstr .= " AND {$insta[1]}, {$inst[0]}.";
    } else {        
        array_shift($insta); // get rid of presenter since already included
        $last = array_pop($insta); // pop the last one, we will manually add it
        $instastr = implode(',',$insta);
        $instastr .= ' AND ' . $last;
        $authorstr .= ", $instastr, {$inst[0]}";
    }
        
    // now process authors from other institutions
    for($i=1; $i<count($authors); $i++) {
        $inst = $authors[$i];
        $insta = $inst[1];
        if (count($insta)==1) {
            // only the presenter ... no coauthors at all
            $authorstr .= " {$insta[0]}, {$inst[0]}.";
        } else if (count($insta)==2) {
            $authorstr .= " {$insta[0]} AND {$insta[1]}, {$inst[0]}.";
        } else {
            // must be lots of authors
            $last = array_pop($insta); // pop the last one, we will manually add it            
            $instastr = implode(',',$insta);
            $instastr .= ' AND ' . $last;
            $authorstr .= " $instastr, {$inst[0]}."; 
        }
    }
//     return print_r($authors,true);
    
    return $authorstr;
}

function addAuthor($author, $coauthorinst, &$authors) {
    // don't do anything if this author wasn't listed
    if (!$author) {
        return;
    }
    
    // First|Last|Inst
    $parts = explode('|',$author);
    $name = strtoupper($parts[0] . ' ' . $parts[1]);
    $inst = isset($parts[2])&&$parts[2]?$parts[2]:$coauthorinst;
    if (!$inst) {
        $inst = 'N/A';
    }
    $inst = strtoupper($inst);
    
    foreach ($authors as &$a) {
        if ($a[0]==$inst) {
            $a[1][] = $name;
            return; // found it let's exit now that we have added author to end of authors array
        }
    }
    
    // didn't find it, time to create new array and populate it with just this author
    $authors[] = [$inst,[$name]];
}

?>
</pre>