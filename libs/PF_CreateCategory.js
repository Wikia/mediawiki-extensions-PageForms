// Enter in a text input would submit the form via its first button, "Save";
// see also PF_CreateForm.js.
$( () => {
	$( '#pfCreateCategoryForm' ).on( 'keypress', 'input', ( event ) => event.key !== 'Enter' );
} );
