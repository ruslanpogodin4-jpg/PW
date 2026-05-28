// Filename  : DlgPreviewShop.h
// Description: Shop dialog for flights (main_type=3) and pets (main_type=4).
//              Reuses the FashionShop item grid UI but without 3D character model.

#pragma once

#include "DlgFashionShop.h"

class CDlgViewModel;

class CDlgPreviewShop : public CDlgFashionShop
{
public:
	CDlgPreviewShop();

	// AUI overrides
	virtual bool OnInitDialog();
	virtual void OnShowDialog();
	virtual void OnTick();

	// Called on item click
	virtual void SelectFashionShopItem(int index);

	// Hide Win_ViewModel when the shop closes
	virtual void ShowFalse();

private:
	void UpdateViewModel(int fashionIndex);
	void HideViewModel();
	void AlignViewModel(CDlgViewModel* pDlg);
};
