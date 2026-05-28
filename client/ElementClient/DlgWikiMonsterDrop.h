// Filename	: DlgWikiMonsterDrop.h
// Creator	: Xu Wenbin
// Date		: 2010/04/07

#pragma once

#include "DlgWikiItemListBase.h"
#include "AUIImagePicture.h"
#include "AUIProgress.h"
#include "elementdataman.h"
#include <vector>

class CDlgWikiMonsterDrop : public CDlgWikiItemListBase
{
	AUI_DECLARE_EVENT_MAP()
	AUI_DECLARE_COMMAND_MAP()

public:
	CDlgWikiMonsterDrop();

	virtual void SetSearchDataPtr(void* ptr);
	virtual bool OnInitDialog();
	virtual void OnShowDialog();

protected:
	virtual bool OnModityItem(WikiEntityPtr p, PAUIOBJECT pObj, PAUIOBJECT pName);
	virtual void OnEndSearch();

	void UpdateStatsDisplay(const MONSTER_ESSENCE* pEssence);

	enum { MAX_ICONS = 16 };
	PAUIIMAGEPICTURE  m_pIcons[MAX_ICONS];
	PAUIPROGRESS      m_pProgress[MAX_ICONS];       // Prgs_01..Prgs_16 (drop item bars)
	PAUIPROGRESS      m_pDropNumProgress[4];         // Prgs_DropNum0..3 (drop count bars)
	const MONSTER_ESSENCE* m_pCachedEssence;

	struct DropEntry {
		unsigned int id;
		float probability;
	};
	std::vector<DropEntry> m_filteredDrop;
};
