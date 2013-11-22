var category = "oldies";
var dUrl = "http://www.dhingana.com";
var browseBaseUrl = "http://www.dhingana.com/hindi/oldies/songs-albums-browse-";

var albums = [];
var html = [];

function start()
{
	for (var i=0;i<26;i++)
	{
		var browseUrl = browseBaseUrl + String.fromCharCode(i + 97);
		getAlbumsHtml(browseUrl);
	}
}

/* POPULATING ALBUMS WITH URL */
function getAlbumsHtml(browseUrl)
{
	$.ajax({
		url: "functions.php",
		data: {
			fName: "getHtml",
			url: browseUrl
		},
		success: function(data)
		{
			html.push(data);
			startParsing();
		}
	});
}

function startParsing()
{
	if (html.length!=26)
		return;

	for (var i=0;i<html.length;i++)
	{
		parseAlbums(html[i]);
		albums = [];
	}
}

function parseAlbums(html)
{
	$(html).find("#allSongsList li").each(function() {
		album = {
			name: $(this).text(),
			url: dUrl + $(this).find("a").attr("href"),
			albumArt: "",
			cast: [],
			year: 0,
			musicDirector: [],
			songs: [],
			category: category
		};
		
		if (isUniqueAlbum(album))
		{
			//console.log("Adding " + album.name);
			albums.push(album);
		}
	});
	
	getSingleHtml();
}

function isUniqueAlbum(album)
{
	for (var i=0;i<albums.length;i++)
	{
		if (albums[i].url==album.url)
			return false;
	}
	return true;
}
/* END */

/* POPULATING EACH ALBUM WITH DATA AND SAVE */

function getSingleHtml()
{
	for (var i=0;i<albums.length;i++)
	{
		//console.log("Fetching data for " + albums[i].name);

		$.ajax({
			url: "functions.php",
			data: {
				fName: "getHtml",
				url: albums[i].url
			},
			async: false,
			success: function(data)
			{
				parseAlbumData(i, data);
			}
		});
	}
}

function parseAlbumData(index, html)
{
	var album = albums[index];
	
	//console.log("Parsing data for " + album.name);

	album.albumArt = $(html).find(".artwork-image").attr("data-imgsrc");

	var counter = 0;
	$(html).find(".detail-metadata-actions-wrapper .meta-list.content-viewport-line").each(function() {

		if (counter==0) //CAST
		{
			$(this).find("a").each(function() {
				album.cast.push($(this).attr("data-search-keyword"));
			});
		}
		else if(counter==1) //Music Director
		{
			$(this).find("a").each(function() {
				album.musicDirector.push($(this).attr("data-search-keyword"));
			});
		}
		else if(counter==2) //Year
		{
			album.year = $(this).find("a").attr("data-search-keyword");
		}

		counter++;
	});

	$(html).find(".listing-row.work.song").each(function()
	{
		album.songs.push($(this).attr("data-id"));
	});

	albums[index] = album;

	saveAlbum(album);
}

function saveAlbum(album)
{
	$.ajax({
		url: "functions.php",
		type: "POST",
		async: false,
		data: {
			fName: "saveAlbum",
			album: album
		},
		success: function(data)
		{
			console.log(data);
		}
	});

}
/* END */