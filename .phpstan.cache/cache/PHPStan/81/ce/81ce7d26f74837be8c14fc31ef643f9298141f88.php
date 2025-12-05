<?php declare(strict_types = 1);

// odsl-/Users/brian/Developer/GitHub/Maintenance/toml/src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/Parser.php' => 
    array (
      0 => 'fb32f048137d5bced16304f441fa6ea1a2b5ffdb',
      1 => 
      array (
        0 => 'cline\\toml\\parser',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\parse',
        1 => 'cline\\toml\\parseimplementation',
        2 => 'cline\\toml\\processexpression',
        3 => 'cline\\toml\\parsecomment',
        4 => 'cline\\toml\\parsekeyvalue',
        5 => 'cline\\toml\\parsekeyname',
        6 => 'cline\\toml\\parsesimplevalue',
        7 => 'cline\\toml\\parseboolean',
        8 => 'cline\\toml\\parseinteger',
        9 => 'cline\\toml\\parsefloat',
        10 => 'cline\\toml\\parsebasicstring',
        11 => 'cline\\toml\\parsemultilinebasicstring',
        12 => 'cline\\toml\\parseliteralstring',
        13 => 'cline\\toml\\parsemultilineliteralstring',
        14 => 'cline\\toml\\parseescapedcharacter',
        15 => 'cline\\toml\\parsedatetime',
        16 => 'cline\\toml\\parsearray',
        17 => 'cline\\toml\\parseinlinetable',
        18 => 'cline\\toml\\parsetable',
        19 => 'cline\\toml\\parsearrayoftables',
        20 => 'cline\\toml\\matchnext',
        21 => 'cline\\toml\\requiretoken',
        22 => 'cline\\toml\\parsespaceifexists',
        23 => 'cline\\toml\\parsecommentifexists',
        24 => 'cline\\toml\\parsecommentsinsideblockifexists',
        25 => 'cline\\toml\\errorifnextisnotnewlineoreos',
        26 => 'cline\\toml\\unexpectedtokenerror',
        27 => 'cline\\toml\\syntaxerror',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/TomlBuilder.php' => 
    array (
      0 => 'fe906749befdd2820bd60793533785d44678eece',
      1 => 
      array (
        0 => 'cline\\toml\\tomlbuilder',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\__construct',
        1 => 'cline\\toml\\addvalue',
        2 => 'cline\\toml\\addtable',
        3 => 'cline\\toml\\addarraytables',
        4 => 'cline\\toml\\addarrayoftable',
        5 => 'cline\\toml\\addcomment',
        6 => 'cline\\toml\\gettomlstring',
        7 => 'cline\\toml\\getescapedcharacters',
        8 => 'cline\\toml\\getspecialcharacters',
        9 => 'cline\\toml\\addkey',
        10 => 'cline\\toml\\addtablekey',
        11 => 'cline\\toml\\addarrayoftablekey',
        12 => 'cline\\toml\\dumpvalue',
        13 => 'cline\\toml\\append',
        14 => 'cline\\toml\\dumpstring',
        15 => 'cline\\toml\\isliteralstring',
        16 => 'cline\\toml\\dumpbool',
        17 => 'cline\\toml\\dumparray',
        18 => 'cline\\toml\\dumpcomment',
        19 => 'cline\\toml\\dumpdatetime',
        20 => 'cline\\toml\\dumpinteger',
        21 => 'cline\\toml\\dumpfloat',
        22 => 'cline\\toml\\isstringvalid',
        23 => 'cline\\toml\\normalizestring',
        24 => 'cline\\toml\\exceptionifkeyempty',
        25 => 'cline\\toml\\exceptionifkeyisnotunquotedkey',
        26 => 'cline\\toml\\isunquotedkey',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/Lexer.php' => 
    array (
      0 => '17d50e8d54373985ab9f6351ede7ae138e84a329',
      1 => 
      array (
        0 => 'cline\\toml\\lexer',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\__construct',
        1 => 'cline\\toml\\tokenize',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/TomlArray.php' => 
    array (
      0 => '5e25522a409708c1cb96a4337548838ee5cf2d21',
      1 => 
      array (
        0 => 'cline\\toml\\tomlarray',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\__construct',
        1 => 'cline\\toml\\addkeyvalue',
        2 => 'cline\\toml\\addtablekey',
        3 => 'cline\\toml\\begininlinetablekey',
        4 => 'cline\\toml\\endcurrentinlinetablekey',
        5 => 'cline\\toml\\addarraytablekey',
        6 => 'cline\\toml\\escapekey',
        7 => 'cline\\toml\\getarray',
        8 => 'cline\\toml\\unescapekey',
        9 => 'cline\\toml\\gotokey',
        10 => 'cline\\toml\\setcurrentpointertolastelement',
        11 => 'cline\\toml\\resetcurrentpointer',
        12 => 'cline\\toml\\existsinarraytablekey',
        13 => 'cline\\toml\\getkeylastelementofarray',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/KeyStore.php' => 
    array (
      0 => '0b8688a6b6d3b3aaa1b71750ee549ad12f82ec18',
      1 => 
      array (
        0 => 'cline\\toml\\keystore',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\addkey',
        1 => 'cline\\toml\\isvalidkey',
        2 => 'cline\\toml\\addtablekey',
        3 => 'cline\\toml\\isvalidtablekey',
        4 => 'cline\\toml\\isvalidinlinetable',
        5 => 'cline\\toml\\addinlinetablekey',
        6 => 'cline\\toml\\addarraytablekey',
        7 => 'cline\\toml\\isvalidarraytablekey',
        8 => 'cline\\toml\\isregisteredastablekey',
        9 => 'cline\\toml\\isregisteredasarraytablekey',
        10 => 'cline\\toml\\istableimplicitfromarrytable',
        11 => 'cline\\toml\\composekeywithcurrentprefix',
        12 => 'cline\\toml\\getarrayoftablekeyfromtablekey',
        13 => 'cline\\toml\\processimplicitarraytablenameifneeded',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/Toml.php' => 
    array (
      0 => '019fc7a95e2bcb877ba738c41cb1a89c7a585667',
      1 => 
      array (
        0 => 'cline\\toml\\toml',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\parse',
        1 => 'cline\\toml\\parsefile',
        2 => 'cline\\toml\\doparse',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/Exception/DumpException.php' => 
    array (
      0 => '21259565247e63f0800b9c8e0f2c9a17753d38c2',
      1 => 
      array (
        0 => 'cline\\toml\\exception\\dumpexception',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/Users/brian/Developer/GitHub/Maintenance/toml/src/Exception/ParseException.php' => 
    array (
      0 => '8b6848c14d62fcfb915134f8f3c5263c0ee71d31',
      1 => 
      array (
        0 => 'cline\\toml\\exception\\parseexception',
      ),
      2 => 
      array (
        0 => 'cline\\toml\\exception\\__construct',
        1 => 'cline\\toml\\exception\\getsnippet',
        2 => 'cline\\toml\\exception\\setsnippet',
        3 => 'cline\\toml\\exception\\getparsedfile',
        4 => 'cline\\toml\\exception\\setparsedfile',
        5 => 'cline\\toml\\exception\\getparsedline',
        6 => 'cline\\toml\\exception\\setparsedline',
        7 => 'cline\\toml\\exception\\updaterepr',
      ),
      3 => 
      array (
      ),
    ),
  ),
));