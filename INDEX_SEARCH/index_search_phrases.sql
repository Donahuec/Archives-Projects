-- Inserts the phrases for packages/core/admin/database.php


INSERT INTO `tblCore_Phrases` (
	`ID`, 
	`PackageID`,
	`ModuleID`,
	`LanguageID`,
	`PhraseName`,
	`PhraseValue`,
	`RegularExpression`,
	`PhraseTypeID`
)
VALUES
(NULL, 1, 11, 2081, 'itemidnum', 'Item ID Number', NULL, 5),
(NULL, 1, 11, 2081, 'collidnum', 'Collection ID Number', NULL, 5),
(NULL, 1, 11, 2081, 'indexallitems', 'Index all items', NULL, 5);

