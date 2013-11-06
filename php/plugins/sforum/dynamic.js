<script type="text/javascript" language="javascript">

<!--

		var uploadFields = [];

		var uploadForm = null;



		function initDocument()

		{

			uploadForm = document.getElementById("dynamicUpload");

			uploadForm.enctype = "multipart/form-data";

			uploadForm.action = "insert.php";



			addUploadField();	

		}

		

		function UploadField_Altered(e)

		{

			var maxUploadFile = -1;



			if (uploadFields[uploadFields.length - 1].value.length > 0)

			{	

				if (maxUploadFile < 0 ||  maxUploadFile > uploadFields.length)

				{

					addUploadField();	

				}

			}

		}



		function addUploadField()

		{

            var br = document.createElement("br");

			var newField = document.createElement("input");

            var submit = document.createElement("input");



			submit.type = "submit";

			submit.value = "send";



			newField.type = "file";

			newField.className = "uploadField";

			newField.name = "uploadField" + uploadFields.length;

			newField.size = "50";

			newField.style.width = "420px";



			newField.ChangedHandler = UploadField_Altered;



			newField.onchange = newField.ChangedHandler;

            newField.onkeypress = disableEvent;

            newField.onkeydown = disableEvent;

            newField.onpaste = disableEvent;

            newField.oncut = disableEvent;

            newField.oncontextmenu = disableEvent;



			uploadForm.appendChild(newField);

			uploadForm.appendChild(submit);

			uploadForm.appendChild(br);

			uploadFields.push(newField);

		}



        function disableEvent()

        {

            return false;

        }

//-->

</script>