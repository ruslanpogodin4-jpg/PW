#include <iostream>
#include <AFilePackMan.h>
#include <windows.h>

int	aAFPCK_GUARDBYTE0 = 0xfdfdfeee;
int	aAFPCK_GUARDBYTE1 = 0xf00dbeef;
int aAFPCK_MASKDWORD = 0xa8937462;
int	aAFPCK_CHECKMASK = 0x59374231;

int 	aAFPCK_MASKDWORDCUSTOM = 0x350DAA8C;
int		aAFPCK_CHECKMASKCUSTOM = 0x3E367B13;
int		aAFPCK_GUARDBYTE0CUSTOM = 0x1207940;
int		aAFPCK_GUARDBYTE1CUSTOM = 0x549D22B4;

int		aAFPCK_SAFETAG1CC = 0x3F071506;
int		aAFPCK_SAFETAG2CC = 0x2A1E5540;

const char* g_szPckDir[] = {
	"configs",
	"facedata",
	"gfx",
	"grasses",
	"help\\script",
	"interfaces",
	"building",
	"models",
	"sfx",
	"shaders",
	"surfaces",
	"textures",
	"trees",
	"litmodels",
	"loddata",
	"evolved"
};

bool SetAlgorithmID(int id)
{
	switch (id)
	{
	case 111:
		aAFPCK_GUARDBYTE0 = 0xab12908f;
		aAFPCK_GUARDBYTE1 = 0xb3231902;
		aAFPCK_MASKDWORD = 0x2a63810e;
		aAFPCK_CHECKMASK = 0x18734563;
		break;

	default:
		aAFPCK_GUARDBYTE0 = 0x72341F2 * id + 0x1207940;
		aAFPCK_GUARDBYTE1 = 0x1237A73 * id + 0x549D22B4;
		aAFPCK_MASKDWORD = 0xAB2321F * id + 0x350DAA8C;
		aAFPCK_CHECKMASK = 0x987A223 * id + 0x3E367B13;
		break;
	}

	return true;
}

int main()
{
	g_AFilePackMan.SetAlgorithmID(171);
	const char* g_szWorkDir = "J:\\downloads\\155EVLVD\\bin";
	for (int i = 0; i < sizeof(g_szPckDir) / sizeof(const char*); i++)
	{
		char szPckFile[MAX_PATH];
		sprintf_s(szPckFile, "%s\\%s.pck", g_szWorkDir, g_szPckDir[i]);
		if (!g_AFilePackMan.OpenFilePackageInGame(szPckFile))
		{
			std::cout << "Failed to open package: " << szPckFile << std::endl;
		}
		else
		{
			std::cout << "Successfully opened package: " << szPckFile << std::endl;

		}
	}
	std::cout << g_AFilePackMan.GetNumPackages() << " packages opened." << std::endl;
	


}

char sub_9357F0(int a1)
{
	int v1; // ecx
	int v2; // edx
	int v3; // esi
	int v4; // eax
	char result; // al

	if (a1 == 111)
	{
		v1 = 410207587;
		v2 = 711164174;
		v3 = -1289545470;
		v4 = -1424846705;
	}
	else
	{
		v1 = 0x987A223 * a1 + 0x3E367B13;
		v2 = 0xAB2321F * a1 + 0x350DAA8C;
		v3 = 0x1237A73 * a1 + 0x549D22B4;
		v4 = 0x72341F2 * a1 + 0x1207940;
	}
	result = 1;
	return result;
}