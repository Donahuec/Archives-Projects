import java.io.*;
import java.util.*;

public class MakeDirectory{
	public String path = "";
	public File readFile = null;
	public String dirName = "";
	public List IDList;
	
	public MakeDirectory(File readFile, String path, String dirName){
		this.readFile = readFile;
		this.path = path;
		this.dirName = dirName;
		makeStartingDirectory();
		IDList = readFile();
		makeIDs();
		System.out.println("Finished");
	}
	
	
	public void makeStartingDirectory() {
		System.out.println("making Starting directory");
		File start = new File(path + dirName);
		start.mkdir();
	}
	
	public void makeIDs() {
		System.out.println("Making ID Folders");
		for (int i = 0; i < IDList.size(); i++) {
			File file = new File(path + dirName + "\\" + IDList.get(i));
			file.mkdir();
		}
	}
	
	public List readFile(){
		System.out.println("Reading File");
		Scanner scanner = null;;
		List list = new ArrayList();
	
		try {
			scanner = new Scanner(readFile);
		} catch (FileNotFoundException e) {
			System.err.println(e);
			System.exit(1);
		}
		
		Scanner line = new Scanner(scanner.nextLine());
		while (scanner.hasNextLine()) {
			line = new Scanner(scanner.nextLine());
			line.useDelimiter("\t");
			list.add(line.next());
			//System.out.println("Id: " + line.next());	
		}
		return list;
	}
	
	public static void main(String[] args) {
		//get goal place for directory
		//get readfile
		//get dirName
		Scanner prompt = new Scanner(System.in);
		System.out.println("Where would you like your directory to be?");
		System.out.println("Please include the full path");
		System.out.println("If you don't know the full path, open a new command prompt or terminal");
		System.out.println("Then navigate to the folder where you would like your new directory to be");
		System.out.println("If you are using windows, type the command cd");
		System.out.println("If you are using a mac, type the command pwd");
		System.out.println("This should display the path to that location");
		System.out.println("Please enter the path here, ending the path with a back or forward slash, depending on the system you are using: ");
		String path = prompt.nextLine().trim();
		System.out.println();
		System.out.println("Now please enter what you would like your directory to be named: ");
		String dirName = prompt.nextLine().trim();
		System.out.println();
		System.out.println("Now, please type the name of the File you would like to make directories from");
		String readFileName = prompt.nextLine().trim();
		File readFile = new File(readFileName);
		while (readFile != null && !readFile.isFile()) {
			System.out.println("File name is invalid");
			System.out.println("please make sure the file name is typed correctly and that the file is either in the same location as this program, or that the full path to the file was included in the name");
			System.out.println("Please type the file name again: ");
			readFileName = prompt.nextLine().trim();
		}
		System.out.println();
		
		System.out.println("Making Directories");
		
		MakeDirectory directory = new MakeDirectory(readFile, path, dirName);
		
	}
	
	
}