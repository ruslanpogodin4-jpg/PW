#include <cstdio>
#include <cstdlib>

int main()
{
    FILE *nFile;
    FILE *ovfile;

    const char *szPackPath = "/home/gamed/config/tasks.data";

    nFile = fopen(szPackPath, "rb");

    long nFileSize; // store file size of the file we want to read

    char *ibuffer; // buffer for reading
    char *eBuffer; // buffer for storing encrypted data

    size_t rResult;
    size_t wResult;

    if (nFile == NULL)
    {
        exit(EXIT_FAILURE);
    }

    fseek(nFile, 0, SEEK_END);
    nFileSize = ftell(nFile);
    rewind(nFile);

    ibuffer = (char *)malloc(nFileSize);
    if (ibuffer == NULL)
    {
        exit(EXIT_FAILURE);
    }

    rResult = fread(ibuffer, 1, nFileSize, nFile);

    if (rResult != nFileSize)
    {

        exit(EXIT_FAILURE);
    }

    fclose(nFile);

    eBuffer = (char *)malloc(sizeof(char) * nFileSize);

    if (eBuffer == NULL)
    {

        exit(EXIT_FAILURE);
    }

    // encrypt byte by byte and save to buffer

    unsigned char key[6] = {'T', 'R', 'O', 'U', 'X', 'A'};

    // encrypt byte by byte and save to buffer

    for (int i = 0; i < nFileSize; i++)
    {
#ifdef BOLA_DEVBUILD
        eBuffer[i] = ibuffer[i];
#else
        unsigned char xortarget = key[i % (sizeof(key) / sizeof(char))];
        if (ibuffer[i] != 0 && ibuffer[i] != xortarget)
        {
            eBuffer[i] = ibuffer[i] ^ xortarget;
        }
        else
        {
            eBuffer[i] = ibuffer[i];
        }
#endif
    }

    char *encryptpath = "/home/bola/build/tasks_temp.data";
    ovfile = fopen(encryptpath, "wb");
    wResult = fwrite(eBuffer, 1, nFileSize, ovfile);
    fclose(ovfile);
    free(eBuffer);
    free(ibuffer);

    FILE *fp = fopen(encryptpath, "rb");
    if (fp == NULL)
        return false;

    printf("File encrypted successfully to %s\n", encryptpath);
    return 0;
}